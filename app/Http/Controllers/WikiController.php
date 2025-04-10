<?php

namespace App\Http\Controllers;

use App\Models\WikiPage;
use App\Models\WikiVersion;
use App\Models\WikiCategory;
use App\Models\WikiTag;
use App\Models\WikiPageDraft;
use App\Models\ActivityLog;
use App\Services\DiffService;
use App\Services\CollaborationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Mews\Purifier\Facades\Purifier;
use Overtrue\LaravelPinyin\Facades\Pinyin;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Illuminate\Database\Eloquent\Model; // 保持引用，因为基类或LogsActivity可能需要
use Illuminate\Auth\Access\AuthorizationException;

class WikiController extends Controller
{
    /**
     * 显示 Wiki 页面列表。
     */
    public function index(Request $request): InertiaResponse
    {
        $query = WikiPage::with(['creator:id,name', 'categories:id,name,slug', 'tags:id,name,slug'])
            ->where('status', WikiPage::STATUS_PUBLISHED);

        // 分类筛选
        if ($request->filled('category')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        // 标签筛选
        if ($request->filled('tag')) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->where('slug', $request->tag);
            });
        }

        // 搜索
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%"); // 或根据需要搜索其他字段
            });
        }

        $pages = $query->latest('updated_at')
            ->paginate(15)
            ->withQueryString(); // 保留查询参数

        $categories = WikiCategory::withCount(['pages' => function ($query) {
                $query->where('status', WikiPage::STATUS_PUBLISHED);
            }])
            ->orderBy('order')
            ->select('id', 'name', 'slug')
            ->get();

        $tags = WikiTag::withCount(['pages' => function ($query) {
                $query->where('status', WikiPage::STATUS_PUBLISHED);
            }])
            ->select('id', 'name', 'slug')
            ->get();

        return Inertia::render('Wiki/Index', [
            'pages' => $pages,
            'categories' => $categories,
            'tags' => $tags,
            'filters' => $request->only(['category', 'tag', 'search'])
        ]);
    }

    /**
     * 显示单个 Wiki 页面。
     */
    public function show(string $slug): InertiaResponse // 改为接收 slug 字符串
    {
        // 1. 通过 slug 查找页面，并预加载部分基本关联
        $page = WikiPage::where('slug', $slug)
            ->with(['creator:id,name', 'categories:id,name,slug', 'tags:id,name,slug'])
            ->firstOrFail(); // 如果找不到页面，会抛出 404

        $currentVersion = null;
        // 2. 如果页面有关联的版本 ID，尝试加载该版本及其创建者
        if ($page->current_version_id) {
            $currentVersion = WikiVersion::with('creator:id,name')->find($page->current_version_id);
        }

        // 3. 处理冲突状态的逻辑 (基本不变)
        if ($page->status === WikiPage::STATUS_CONFLICT && !Auth::user()?->hasPermission('wiki.resolve_conflict')) {
            // 确保创建者信息已加载，以便在冲突页面显示
            $page->loadMissing('creator:id,name');
            return Inertia::render('Wiki/Conflict', [
                'page' => $page->only('id', 'title', 'slug', 'creator'), // 传递需要的信息
                'message' => '此页面当前存在编辑冲突，需要管理员解决。'
            ]);
        }

        // 4. 处理版本加载失败的情况
         if ($page->current_version_id && !$currentVersion) {
             Log::error("WikiPage ID {$page->id} ('{$page->title}') has current_version_id={$page->current_version_id}, but WikiVersion not found.");
            // 即使版本内容加载失败，仍然尝试加载评论
            $page->load(['comments' => function($query) {
                $query->where('is_hidden', false)
                      ->whereNull('parent_id')
                      ->with(['user:id,name', 'replies' => function($q) {
                          $q->where('is_hidden', false)->with('user:id,name')->latest('created_at');
                      }])
                      ->latest('created_at');
            }]);
              return Inertia::render('Wiki/Show', [
                 'page' => $page, // 传递页面基本信息
                 'currentVersion' => null, // 明确传递 null
                 'error' => '无法加载页面内容版本，请联系管理员。', // 传递错误信息
                 'comments' => $page->comments ?? [], // 确保 comments 总是数组
                 'canEdit' => Auth::user()?->can('update', $page) ?? false,
                 'canResolveConflict' => Auth::user()?->hasPermission('wiki.resolve_conflict') ?? false,
                 'isLocked' => false, // 内容加载失败时认为未锁定
                 'lockedBy' => null,
                 'draft' => null, // 草稿信息可能也无需加载了
                 'flash' => session('flash'),
             ]);
         }

        // 5. 如果版本存在或者页面本就没有版本 ID，加载评论
        $page->load(['comments' => function($query) {
            $query->where('is_hidden', false)
                    ->whereNull('parent_id')
                    ->with(['user:id,name', 'replies' => function($q) {
                        $q->where('is_hidden', false)->with('user:id,name')->latest('created_at');
                    }])
                    ->latest('created_at');
        }]);

        // 6. 处理页面锁定状态
        $isLocked = $page->isLocked();
        $lockedBy = null;
        if($isLocked){
             $page->loadMissing('locker:id,name'); // 使用 loadMissing 避免重复加载
             $lockedBy = $page->locker;
        }

        // 7. 获取用户草稿
        $draft = null;
        if (Auth::check()) {
            $draft = WikiPageDraft::where('wiki_page_id', $page->id)
                ->where('user_id', Auth::id())
                ->select('content', 'last_saved_at')
                ->first();
        }

        // 8. 正常返回数据给视图
        return Inertia::render('Wiki/Show', [
            'page' => $page, // 包含 creator, categories, tags, comments, locker(如果锁定)
            'currentVersion' => $currentVersion, // 显式传递当前版本 (可能为 null)
            'isLocked' => $isLocked,
            'lockedBy' => $lockedBy ? $lockedBy->only('id', 'name') : null,
            'draft' => $draft,
            'canEdit' => Auth::user()?->can('update', $page) ?? false,
            'canResolveConflict' => Auth::user()?->hasPermission('wiki.resolve_conflict') ?? false,
            'error' => session('error'), // 保留 flash/session 错误
            'flash' => session('flash'),
        ]);
    }

    /**
     * 显示创建 Wiki 页面的表单。
     */
    public function create(): InertiaResponse
    {
        // $this->authorize('wiki.create'); // 权限检查移到路由层
        $categories = WikiCategory::select('id', 'name')->orderBy('order')->get();
        $tags = WikiTag::select('id', 'name')->get();

        return Inertia::render('Wiki/Create', [
            'categories' => $categories,
            'tags' => $tags,
            // 移除 'pages' 传递，因为不再需要选择父页面
        ]);
    }

    /**
     * 存储新创建的 Wiki 页面。
     */
    public function store(Request $request)
    {
        // $this->authorize('wiki.create'); // 权限检查移到路由层
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'required|exists:wiki_categories,id',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:wiki_tags,id',
        ]);

        $validated['content'] = Purifier::clean($validated['content']);

        // 生成 Slug
        $slug = Str::slug($validated['title']);
        if (empty($slug)) {
            $slug = Pinyin::permalink($validated['title'], '-');
        }
        $originalSlug = $slug;
        $counter = 1;
        while (WikiPage::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter++;
        }

        DB::beginTransaction();
        try {
            // 创建 WikiPage，移除 parent_id
            $page = WikiPage::create([
                'title' => $validated['title'],
                'slug' => $slug,
                'status' => WikiPage::STATUS_DRAFT, // 先设置为草稿
                'created_by' => Auth::id(),
                'current_version_id' => null, // 初始为 null
            ]);

            // 创建第一个 WikiVersion
            $version = WikiVersion::create([
                'wiki_page_id' => $page->id,
                'content' => $validated['content'],
                'created_by' => Auth::id(),
                'version_number' => 1,
                'comment' => '初始版本',
                'is_current' => true, // 标记为当前版本
            ]);

            // 更新 WikiPage 的 current_version_id 和 status
            $page->update([
                'current_version_id' => $version->id,
                'status' => WikiPage::STATUS_PUBLISHED // 设置为已发布
            ]);

            // 关联分类和标签
            $page->categories()->attach($validated['category_ids']);
            if (!empty($validated['tag_ids'])) {
                $page->tags()->attach($validated['tag_ids']);
            }

            // 记录活动日志
            $this->logActivity('create', $page, [
                'version' => $version->version_number
            ]);

            DB::commit(); // 提交事务

            Log::info("Wiki page {$page->id} ('{$page->title}') created successfully by user " . Auth::id());

            return redirect()->route('wiki.show', $page->slug)
                ->with('flash', ['message' => ['type' => 'success', 'text' => '页面创建成功！']]);

        } catch (\Exception $e) {
            DB::rollBack(); // 回滚事务
            Log::error("Error creating wiki page: " . $e->getMessage());
            // 返回错误信息给用户
            return back()->withErrors(['general' => '创建页面时出错，请稍后重试。'])->withInput();
        }
    }

    /**
     * 显示编辑 Wiki 页面的表单。
     */
    public function edit(WikiPage $page): InertiaResponse
    {
        $user = Auth::user();

        // 检查页面是否处于冲突状态且用户无权解决
        if ($page->status === WikiPage::STATUS_CONFLICT && !$user->hasPermission('wiki.resolve_conflict')) {
            return redirect()->route('wiki.show', $page->slug)
                ->with('flash', ['message' => ['type' => 'error', 'text' => '该页面存在编辑冲突，无法编辑，需要管理员解决。']]);
        }

        // 处理页面锁定
        $lockInfo = null;
        $isLockedByCurrentUser = false;

        if ($page->isLocked()) {
            // 如果页面已被锁定
            if ($page->locked_by !== $user->id) {
                // 如果被其他用户锁定
                Log::warning("User {$user->id} attempting to edit locked page {$page->id} (Locked by {$page->locked_by})");
                 $page->load('locker:id,name'); // 加载锁定者信息
                 $lockInfo = [
                    'isLocked' => true,
                    'lockedBy' => $page->locker,
                    'lockedUntil' => $page->locked_until ? $page->locked_until->format('Y-m-d H:i:s') : null
                 ];
            } else {
                // 如果是当前用户自己锁定的，尝试刷新锁
                if ($page->refreshLock()) {
                    $isLockedByCurrentUser = true;
                    $lockInfo = [
                        'isLocked' => true,
                        'lockedBy' => $user->only('id', 'name'),
                        'lockedUntil' => $page->locked_until->format('Y-m-d H:i:s')
                    ];
                     Log::info("User {$user->id} continues editing page {$page->id}, lock refreshed.");
                } else {
                     // 刷新失败，可能是锁刚好过期，尝试重新获取
                     Log::warning("User {$user->id} failed to refresh lock for page {$page->id}, attempting to re-acquire.");
                     $page->lock($user); // 尝试重新锁定
                     $isLockedByCurrentUser = true;
                     $lockInfo = [
                        'isLocked' => true,
                        'lockedBy' => $user->only('id', 'name'),
                        'lockedUntil' => $page->locked_until->format('Y-m-d H:i:s')
                     ];
                }
            }
        } else {
            // 如果页面未被锁定，当前用户锁定它
            $page->lock($user);
            $isLockedByCurrentUser = true;
             $lockInfo = [
                'isLocked' => true,
                'lockedBy' => $user->only('id', 'name'),
                'lockedUntil' => $page->locked_until->format('Y-m-d H:i:s') // 确保更新后有 locked_until
             ];
             Log::info("User {$user->id} started editing page {$page->id}, page locked.");
        }


        // 获取草稿内容或当前版本内容
        $draft = null;
        if ($isLockedByCurrentUser) { // $isLockedByCurrentUser 需要在前面锁定逻辑中被设置
             $draft = WikiPageDraft::where('wiki_page_id', $page->id)
                ->where('user_id', $user->id)
                ->select('content', 'last_saved_at')
                ->orderBy('last_saved_at', 'desc')
                ->first();
        }

         // 确定编辑器内容：优先用草稿，否则用当前版本
         $content = $draft ? $draft->content : ($page->currentVersion ? $page->currentVersion->content : '');


        // 加载编辑页面所需的基础数据
        $page->load(['currentVersion:id,version_number', 'categories:id', 'tags:id']); // 仅加载 ID
        $categories = WikiCategory::select('id', 'name')->orderBy('name')->get();
        $tags = WikiTag::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Wiki/Edit', [
            'page' => array_merge(
                 $page->only('id', 'title', 'slug', 'current_version_id', 'status'),
                 [
                     // 传递 ID 数组给前端多选框/组件
                     'category_ids' => $page->categories->pluck('id')->toArray(),
                     'tag_ids' => $page->tags->pluck('id')->toArray(),
                 ]
             ),
            'content' => $content,
            'categories' => $categories,
            'tags' => $tags,
            'hasDraft' => !is_null($draft), // 告知前端是否有草稿
            'lockInfo' => $lockInfo,
            'errors' => session('errors') ? session('errors')->getBag('default')->getMessages() : (object)[], // 传递错误信息
            'flash' => session('flash'), // 传递 Flash 消息
        ]);
    }


    /**
     * 更新指定的 Wiki 页面。
     */
    public function update(Request $request, WikiPage $page): mixed // mixed 表示可能返回 RedirectResponse 或其他类型
    {
        $user = Auth::user();

        // 重新获取最新的页面状态，防止竞态条件
        $page->refresh();

        // 检查是否被其他用户锁定
        if ($page->isLockedByAnotherUser()) {
            Log::warning("Update conflict: User {$user->id} tried to update page {$page->id} locked by {$page->locked_by}.");
             return back()->withErrors(['general' => '无法保存！页面当前正被 ' . ($page->locker->name ?? '其他用户') . ' 编辑中。请刷新页面查看最新内容或稍后再试。'])->withInput();
        }

        // 检查页面是否处于冲突状态且用户无权解决
        if ($page->status === WikiPage::STATUS_CONFLICT && !$user->hasPermission('wiki.resolve_conflict')) {
             Log::warning("Update conflict: User {$user->id} tried to update page {$page->id} which is in conflict status.");
            return back()->withErrors(['general' => '无法保存，页面当前处于冲突状态，需要管理员解决。'])->withInput();
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'required|exists:wiki_categories,id',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:wiki_tags,id',
            'comment' => 'nullable|string|max:255',
            'version_id' => 'required|integer|exists:wiki_versions,id,wiki_page_id,'.$page->id // 确保提交的版本ID属于该页面
        ]);

        // 清理内容
        $newContent = Purifier::clean($validated['content']);

        // 冲突检测逻辑
        $currentVersionIdInDb = $page->current_version_id;
        if ($currentVersionIdInDb != $validated['version_id']) {
            // 用户开始编辑时基于的版本与数据库当前最新版本不一致，可能存在冲突
             Log::info("Conflict Check for page {$page->id}: Submitted base version {$validated['version_id']} vs Current DB version {$currentVersionIdInDb}");

            $baseVersion = WikiVersion::find($validated['version_id']);
            $currentDbVersion = WikiVersion::find($currentVersionIdInDb);

            if ($baseVersion && $currentDbVersion) {
                // 使用 DiffService 进行内容比较
                 $diffService = app(DiffService::class);
                 if ($diffService->hasConflict($baseVersion->content, $currentDbVersion->content, $newContent)) {
                     // 检测到冲突
                    Log::warning("Conflict DETECTED on page {$page->id} by DiffService. User: {$user->id}, Base: {$validated['version_id']}, Current: {$currentVersionIdInDb}");

                    // 1. 标记页面为冲突状态
                    $page->markAsConflict();

                    // 2. 将当前用户的提交保存为一个非当前版本（冲突版本）
                    $latestVersionNumber = $page->versions()->latest('version_number')->value('version_number') ?? 0;
                    WikiVersion::create([
                        'wiki_page_id' => $page->id, 'content' => $newContent,
                        'created_by' => $user->id, 'version_number' => $latestVersionNumber + 1, // 新版本号
                        'comment' => $validated['comment'] ?: '提交时检测到冲突', 'is_current' => false, // 标记为非当前
                    ]);

                    // 3. 清除用户草稿
                     WikiPageDraft::where('wiki_page_id', $page->id)->where('user_id', $user->id)->delete();

                     // 4. 重定向到显示页面，并附带冲突提示
                    return redirect()->route('wiki.show', $page->slug)
                        ->with('flash', ['message' => ['type' => 'error', 'text' => '编辑冲突：在您编辑期间，其他用户已修改此页面。您的更改已保存为冲突版本，请联系管理员解决。']]);

                 } else {
                     // DiffService 未检测到冲突，尽管版本号不匹配（可能是无实质内容修改的保存）
                     Log::info("No conflict detected by DiffService for page {$page->id} despite version mismatch. Proceeding with update.");
                 }
            } else {
                 // 如果找不到基础版本或当前DB版本，可能是数据问题，阻止更新
                 Log::error("Conflict check failed for page {$page->id}: Base version {$validated['version_id']} or Current DB version {$currentVersionIdInDb} not found.");
                return back()->withErrors(['general' => '无法验证页面版本，请刷新后重试。'])->withInput();
            }
        }

        // --- 如果没有冲突或冲突已被解决（通过权限） ---
        DB::beginTransaction();
        try {
             // 如果页面之前是冲突状态，且当前用户有权限解决，则标记为已解决
             if ($page->status === WikiPage::STATUS_CONFLICT && $user->hasPermission('wiki.resolve_conflict')) {
                 Log::info("User {$user->id} resolving conflict for page {$page->id} via normal update.");
                 $page->markAsResolved($user); // 更改状态
             }

            // 更新页面标题
            $page->update(['title' => $validated['title']]);

            // 创建新版本
             $latestVersionNumber = $page->versions()->latest('version_number')->value('version_number') ?? 0;
            $newVersion = WikiVersion::create([
                'wiki_page_id' => $page->id, 'content' => $newContent,
                'created_by' => $user->id, 'version_number' => $latestVersionNumber + 1, // 版本号递增
                'comment' => $validated['comment'] ?: '更新页面', 'is_current' => true, // 设为当前版本
            ]);

            // 将旧版本标记为非当前
            $page->versions()->where('id', '!=', $newVersion->id)->update(['is_current' => false]);

            // 更新页面的当前版本ID
            $page->update(['current_version_id' => $newVersion->id]);

            // 同步分类和标签
            $page->categories()->sync($validated['category_ids']);
            $page->tags()->sync($validated['tag_ids'] ?? []); // 处理可能为空的 tag_ids

            // 删除草稿
            WikiPageDraft::where('wiki_page_id', $page->id)->where('user_id', $user->id)->delete();

            // 解锁页面
            $page->unlock();

            // 记录活动日志
            $this->logActivity('update', $page, ['version' => $newVersion->version_number]);

            DB::commit();

            Log::info("Page {$page->id} updated successfully to version {$newVersion->version_number} by user {$user->id}.");

            return redirect()->route('wiki.show', $page->slug)
                ->with('flash', ['message' => ['type' => 'success', 'text' => '页面更新成功！']]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error updating page {$page->id} by user {$user->id}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return back()->withErrors(['general' => '保存页面时发生内部错误，请稍后重试。'])->withInput();
        }
    }


    /**
     * 手动解锁页面。
     */
    public function unlock(Request $request): JsonResponse
    {
        $validated = $request->validate(['page_id' => 'required|exists:wiki_pages,id']);
        $page = WikiPage::findOrFail($validated['page_id']);
        $user = Auth::user();

         if(!$user){
             // 未登录用户不能解锁
             return response()->json(['message' => '需要登录才能执行此操作', 'success' => false], SymfonyResponse::HTTP_UNAUTHORIZED);
         }

        // 允许锁定者自己解锁，或者有权限的用户（如管理员）强制解锁
        if ($page->locked_by === $user->id || $user->hasPermission('wiki.resolve_conflict')) { // 使用 resolve_conflict 权限作为强制解锁权限
            $page->unlock();
             Log::info("User {$user->id} unlocked page {$page->id}.");
            return response()->json(['message' => '页面已解锁', 'success' => true]);
        } else {
             // 其他情况（页面未锁定、被他人锁定且无权限）则不允许解锁
             Log::warning("User {$user->id} failed attempt to unlock page {$page->id} (Locked by: {$page->locked_by}). No permission.");
            return response()->json(['message' => '您无权解锁此页面', 'success' => false], SymfonyResponse::HTTP_FORBIDDEN);
        }
    }

    /**
     * 刷新页面锁定。
     */
    public function refreshLock(Request $request): JsonResponse
    {
        $validated = $request->validate(['page_id' => 'required|exists:wiki_pages,id']);
        $page = WikiPage::findOrFail($validated['page_id']);
        $user = Auth::user();

         if(!$user){
             return response()->json(['message' => '需要登录才能执行此操作', 'success' => false], SymfonyResponse::HTTP_UNAUTHORIZED);
         }

        // 确保页面被当前用户锁定
        if ($page->isLocked() && $page->locked_by === $user->id) {
            if ($page->refreshLock()) {
                 // Log::debug("User {$user->id} refreshed lock for page {$page->id}. New expiry: {$page->locked_until}");
                 return response()->json([
                    'message' => '锁定时间已刷新',
                    'locked_until' => $page->locked_until->format('Y-m-d H:i:s'), // 返回新的到期时间
                    'success' => true
                 ]);
            } else {
                 // refreshLock 内部逻辑可能失败（尽管在这里不太可能，除非isLocked判断后状态改变）
                 Log::warning("User {$user->id} failed to refresh lock for page {$page->id}. Lock might have expired just before refresh.");
                  return response()->json(['message' => '无法刷新锁定时间，锁可能已过期', 'success' => false], SymfonyResponse::HTTP_CONFLICT);
            }
        } else {
             Log::warning("User {$user->id} failed attempt to refresh lock for page {$page->id}. Not locked by user or not locked at all.");
            // 告知用户为何失败
            return response()->json([
                'message' => '无法刷新锁定时间（页面未被您锁定或已解锁/过期）',
                'success' => false
            ], SymfonyResponse::HTTP_FORBIDDEN); // 或 409 Conflict
        }
    }

    /**
     * 保存页面草稿。
     */
    public function saveDraft(Request $request, $pageId): JsonResponse
    {
        $page = WikiPage::findOrFail((int) $pageId); // 强制转换为整数
        $user = Auth::user();

         if(!$user){
             return response()->json(['message' => '需要登录才能执行此操作'], SymfonyResponse::HTTP_UNAUTHORIZED);
         }

        // 检查锁定状态
        if ($page->isLockedByAnotherUser()) {
             Log::warning("User {$user->id} failed to save draft for page {$page->id} locked by {$page->locked_by}.");
            return response()->json(['message' => '页面已被其他用户锁定，无法保存草稿'], SymfonyResponse::HTTP_CONFLICT);
        }

        $validated = $request->validate(['content' => 'required|string']);

        try {
            $draft = WikiPageDraft::updateOrCreate(
                ['wiki_page_id' => $page->id, 'user_id' => $user->id],
                ['content' => Purifier::clean($validated['content']), 'last_saved_at' => now()]
            );
             Log::info("Draft saved for page {$page->id} by user {$user->id}. Draft ID: {$draft->id}");
            return response()->json([
                'message' => '草稿已自动保存',
                'saved_at' => $draft->last_saved_at->format('Y-m-d H:i:s'),
                'draft_id' => $draft->id, // 可以返回草稿ID
                'success' => true,
            ]);
        } catch (\Exception $e) {
             Log::error("Error saving draft for page {$page->id} by user {$user->id}: " . $e->getMessage());
             return response()->json(['message' => '保存草稿时出错', 'success' => false], SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 删除指定的 Wiki 页面。
     */
    public function destroy(WikiPage $page)
    {
        DB::beginTransaction();
        try {
            $pageTitle = $page->title; // 记录页面标题用于日志
            $pageId = $page->id;
            $userId = Auth::id();

            // 在这里可以添加权限检查，例如 $this->authorize('delete', $page);

            $page->delete(); // 这将触发模型事件和可能的级联删除

            // 记录活动日志
            $this->logActivity('delete', $page, ['title' => $pageTitle]);

            DB::commit();
            Log::info("Wiki page {$pageId} ('{$pageTitle}') deleted by user {$userId}.");
            return redirect()->route('wiki.index')
                ->with('flash', ['message' => ['type' => 'success', 'text' => '页面已删除！']]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error deleting page {$page->id}: " . $e->getMessage());
            return back()->withErrors(['general' => '删除页面时出错，请稍后重试。']);
        }
    }

    /**
      * 显示指定版本的 Wiki 页面内容。
      */
     public function showVersion(WikiPage $page, int $versionNumber): InertiaResponse
     {
         // 确保版本号对应的版本存在且属于该页面
         $versionRecord = WikiVersion::where('wiki_page_id', $page->id)
             ->where('version_number', $versionNumber)
             ->with('creator:id,name') // 预加载创建者信息
             ->firstOrFail(); // 如果找不到，会抛出 404

         // 加载页面基础信息
         $page->load(['creator:id,name', 'categories:id,name,slug', 'tags:id,name,slug']);

         return Inertia::render('Wiki/ShowVersion', [
             'page' => $page->only('id', 'title', 'slug', 'creator', 'categories', 'tags'),
             'version' => $versionRecord->only('id', 'version_number', 'content', 'comment', 'created_at', 'creator'),
         ]);
     }

    /**
     * 显示页面的版本历史。
     */
     public function history(WikiPage $page): InertiaResponse
     {
         $versions = WikiVersion::where('wiki_page_id', $page->id)
             ->with('creator:id,name') // 预加载创建者
             ->orderBy('version_number', 'desc') // 按版本号降序
             ->select('id', 'version_number', 'comment', 'created_at', 'created_by', 'is_current') // 选择需要的字段
             ->paginate(15); // 使用分页

         return Inertia::render('Wiki/History', [
             'page' => $page->only('id', 'title', 'slug'), // 传递页面基本信息
             'versions' => $versions // 传递分页后的版本数据
         ]);
     }

    /**
     * 比较两个版本的差异。
     */
     public function compareVersions(WikiPage $page, int $fromVersionNumber, int $toVersionNumber): InertiaResponse
     {
         // 不能比较同一个版本
         if ($fromVersionNumber === $toVersionNumber) {
             return redirect()->route('wiki.history', $page->slug)
                 ->with('flash', ['message' => ['type' => 'warning', 'text' => '请选择两个不同的版本进行比较。']]);
         }

         // 获取两个版本记录
         $from = WikiVersion::where('wiki_page_id', $page->id)
             ->where('version_number', $fromVersionNumber)
             ->with('creator:id,name')
             ->firstOrFail();

         $to = WikiVersion::where('wiki_page_id', $page->id)
             ->where('version_number', $toVersionNumber)
             ->with('creator:id,name')
             ->firstOrFail();

        // 生成差异视图
        $diffService = app(DiffService::class);
        $diffHtml = $diffService->generateDiffHtml($from->content, $to->content);


         return Inertia::render('Wiki/Compare', [
             'page' => $page->only('id', 'title', 'slug'),
             'fromVersion' => $from->only('id', 'version_number', 'content', 'comment', 'created_at', 'creator'),
             'toVersion' => $to->only('id', 'version_number', 'content', 'comment', 'created_at', 'creator'),
             'diffHtml' => $diffHtml,
         ]);
     }

    /**
     * 恢复页面到指定版本。
     */
     public function revertToVersion(Request $request, WikiPage $page, int $versionNumberToRevertTo)
     {
         $user = Auth::user();

         // 查找要恢复的版本
         $versionToRevert = WikiVersion::where('wiki_page_id', $page->id)
             ->where('version_number', $versionNumberToRevertTo)
             ->firstOrFail();

        // 检查页面锁定状态
        if ($page->isLockedByAnotherUser()) {
            return back()->withErrors(['general' => '无法恢复版本，页面当前正被其他用户编辑。']);
        }
        // 检查页面冲突状态
         if ($page->status === WikiPage::STATUS_CONFLICT) {
             return back()->withErrors(['general' => '无法恢复版本，页面当前处于冲突状态，请先解决冲突。']);
         }

         DB::beginTransaction();
         try {
            // 创建一个基于恢复版本内容的新版本
            $latestVersionNumber = $page->versions()->latest('version_number')->value('version_number') ?? 0;
            $newVersion = WikiVersion::create([
                'wiki_page_id' => $page->id,
                'content' => $versionToRevert->content, // 使用要恢复版本的内容
                'created_by' => $user->id,
                'version_number' => $latestVersionNumber + 1, // 新版本号
                'comment' => '恢复自版本 ' . $versionNumberToRevertTo,
                'is_current' => true, // 设为当前版本
            ]);

            // 将所有其他版本设为非当前
             $page->versions()->where('id', '!=', $newVersion->id)->update(['is_current' => false]);

            // 更新页面的当前版本ID
            $page->update(['current_version_id' => $newVersion->id]);

            // 解锁页面
             $page->unlock();

            // 记录活动日志
             $this->logActivity('revert', $page, [
                'reverted_to_version' => $versionNumberToRevertTo,
                'new_version' => $newVersion->version_number
             ]);

            DB::commit();
             Log::info("Page {$page->id} reverted to version {$versionNumberToRevertTo} by user {$user->id}. New version: {$newVersion->version_number}");
            return redirect()->route('wiki.show', $page->slug)
                ->with('flash', ['message' => ['type' => 'success', 'text' => '页面已恢复到版本 ' . $versionNumberToRevertTo]]);
         } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error reverting page {$page->id} to version {$versionNumberToRevertTo} by user {$user->id}: " . $e->getMessage());
            return back()->withErrors(['general' => '恢复版本时出错，请稍后重试。']);
         }
     }

    /**
      * 显示冲突解决界面。
      */
    public function showConflicts(WikiPage $page, DiffService $diffService): InertiaResponse
    {
        // Use Gate or Policy for authorization (more standard Laravel way)
        // Assuming a Gate 'resolve-conflict' is defined or using direct permission check
         if (!Auth::user() || !Auth::user()->hasPermission('wiki.resolve_conflict')) {
             return $this->unauthorized('您无权解决此页面的冲突。');
         }
        // $this->authorize('resolveConflict', $page); // Alternative using Policy

        if ($page->status !== WikiPage::STATUS_CONFLICT) {
            return redirect()->route('wiki.show', $page->slug)
                ->with('flash', ['message' => ['type' => 'info', 'text' => '该页面当前没有冲突需要解决。']]);
        }

        $currentVersion = $page->currentVersion()->with('creator:id,name')->first();

        // Conflicting version logic (assuming the last non-current version)
        $conflictingVersion = $page->versions()
            ->where('is_current', false)
            // ->where('id', '!=', $currentVersion?->id) // Might not be necessary if is_current logic is strict
            ->orderBy('version_number', 'desc')
            ->with('creator:id,name')
            ->first();

        if (!$currentVersion || !$conflictingVersion) {
            Log::error("Conflict resolution error for page {$page->id}: Missing current or conflicting version data.");
             return redirect()->route('wiki.show', $page->slug)
                ->with('flash', ['message' => ['type' => 'error', 'text' => '无法加载冲突版本信息，请联系管理员。']]);
        }

        $diffHtml = $diffService->generateDiffHtml($conflictingVersion->content, $currentVersion->content);

        return Inertia::render('Wiki/ShowConflicts', [
            'page' => $page->only('id', 'title', 'slug'),
            'conflictVersions' => [
                'current' => $currentVersion ? $currentVersion->only('id', 'version_number', 'content', 'comment', 'created_at', 'creator') : null,
                'conflict' => $conflictingVersion ? $conflictingVersion->only('id', 'version_number', 'content', 'comment', 'created_at', 'creator') : null,
            ],
            'diffHtml' => $diffHtml,
        ]);
    }



    /**
     * 解决编辑冲突。
     */
    public function resolveConflict(Request $request, WikiPage $page): mixed
    {
         // Use Gate or Policy for authorization
        if (!Auth::user() || !Auth::user()->hasPermission('wiki.resolve_conflict')) {
            // Using response() helper for API-like contexts or back() for forms
             return back()->withErrors(['general' => '您无权解决此页面的冲突。'])->withInput();
        }
        // $this->authorize('resolveConflict', $page); // Alternative using Policy

        if ($page->status !== WikiPage::STATUS_CONFLICT) {
             return redirect()->route('wiki.show', $page->slug)
                ->with('flash', ['message' => ['type' => 'info', 'text' => '该页面当前没有冲突需要解决。']]);
        }

        $validated = $request->validate([
            'content' => 'required|string',
            'resolution_comment' => 'nullable|string|max:255'
        ]);

        $user = Auth::user();

        DB::beginTransaction();
        try {
            // Use Purifier for the resolved content
            $cleanContent = Purifier::clean($validated['content']);

            $latestVersionNumber = $page->versions()->where('wiki_page_id', $page->id)->latest('version_number')->value('version_number') ?? 0;

            $newVersion = WikiVersion::create([
                'wiki_page_id' => $page->id,
                'content' => $cleanContent, // Use cleaned content
                'created_by' => $user->id,
                'version_number' => $latestVersionNumber + 1,
                'comment' => $validated['resolution_comment'] ?: '解决编辑冲突',
                'is_current' => true,
            ]);

            // Mark all other versions as not current
            WikiVersion::where('wiki_page_id', $page->id)
                       ->where('id', '!=', $newVersion->id)
                       ->update(['is_current' => false]);

            // Update the page to point to the new version and mark as resolved
            $page->update(['current_version_id' => $newVersion->id]);
            $page->markAsResolved($user); // This method should also unlock the page

            // Log activity using the Trait method if available, or the static method
             if (method_exists($page, 'logCustomActivity')) {
                 $page->logCustomActivity('conflict_resolved', ['resolved_by' => $user->id, 'new_version' => $newVersion->version_number]);
             } else {
                 ActivityLog::log('conflict_resolved', $page, ['resolved_by' => $user->id, 'new_version' => $newVersion->version_number]);
             }

            DB::commit();
            Log::info("Conflict for page {$page->id} resolved by user {$user->id}. New version: {$newVersion->version_number}");

            return redirect()->route('wiki.show', $page->slug)
                ->with('flash', ['message' => ['type' => 'success', 'text' => '页面冲突已成功解决！']]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error resolving conflict for page {$page->id} by user {$user->id}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            // Use Inertia validation errors for better feedback
            return back()->withErrors(['general' => '解决冲突时发生内部错误，请稍后重试。'])->withInput();
        }
    }

     /**
     * 获取当前编辑该页面的用户列表。
     */
    public function getEditors(WikiPage $page, CollaborationService $collaborationService): JsonResponse
    {
        $editors = $collaborationService->getEditors($page->id);
        return response()->json(['editors' => array_values($editors)]); // 返回纯数组
    }

    /**
     * 注册用户为当前页面的编辑者（心跳）。
     */
    public function registerEditor(Request $request, WikiPage $page, CollaborationService $collaborationService): JsonResponse
    {
         if (!Auth::check()) {
            return response()->json(['message' => '需要登录'], SymfonyResponse::HTTP_UNAUTHORIZED);
         }
        $collaborationService->registerEditor($page, Auth::user());
        return response()->json(['success' => true, 'message' => '已注册为页面编辑者']);
    }

    /**
     * 注销用户离开页面的编辑状态。
     */
    public function unregisterEditor(Request $request, WikiPage $page, CollaborationService $collaborationService): JsonResponse
    {
         // 即使未登录也返回成功，避免前端报错
         if (!Auth::check()) {
             return response()->json(['success' => true, 'message' => '用户未登录，无需注销']);
         }
        $collaborationService->unregisterEditor($page, Auth::user());
        return response()->json(['success' => true, 'message' => '已注销编辑状态']);
    }

    /**
     * 获取页面的实时讨论消息。
     */
    public function getDiscussionMessages(WikiPage $page, CollaborationService $collaborationService): JsonResponse
    {
        $messages = $collaborationService->getDiscussionMessages($page->id);
        return response()->json(['messages' => $messages]);
    }

    /**
     * 发送实时讨论消息。
     */
    public function sendDiscussionMessage(Request $request, WikiPage $page, CollaborationService $collaborationService): JsonResponse
    {
        $validated = $request->validate(['message' => 'required|string|max:500']); // 限制消息长度
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => '请先登录'], SymfonyResponse::HTTP_UNAUTHORIZED);
        }

        try {
            $message = $collaborationService->addDiscussionMessage($page, $user, $validated['message']);
            return response()->json(['success' => true, 'message' => $message]); // 返回刚发送的消息
        } catch (\Exception $e) {
            Log::error("Error sending discussion message for page {$page->id} by user {$user->id}: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => '发送消息失败'], SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * 记录活动日志的辅助方法。
     */
     protected function logActivity(string $action, Model $subject, ?array $properties = null): void
    {
         if (app()->runningInConsole()) return;
         try {
            ActivityLog::log($action, $subject, $properties);
         } catch (\Exception $e) {
             Log::error("Failed to log activity: Action={$action}, Subject={$subject->getTable()}:{$subject->getKey()}, Error: " . $e->getMessage());
         }
    }
}
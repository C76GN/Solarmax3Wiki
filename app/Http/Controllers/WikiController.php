<?php

namespace App\Http\Controllers;

use App\Events\WikiPageVersionUpdated;
use App\Models\ActivityLog;
use App\Models\WikiCategory;
use App\Models\WikiPage;
use App\Models\WikiPageDraft;
use App\Models\WikiTag;
use App\Models\WikiVersion;
use App\Services\CollaborationService;
use App\Services\DiffService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Mews\Purifier\Facades\Purifier;
use Overtrue\LaravelPinyin\Facades\Pinyin;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class WikiController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $query = WikiPage::with(['creator:id,name', 'categories:id,name,slug', 'tags:id,name,slug'])
            ->where('status', WikiPage::STATUS_PUBLISHED);

        if ($request->filled('category')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }
        if ($request->filled('tag')) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->where('slug', $request->tag);
            });
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $pages = $query->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

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
            'filters' => $request->only(['category', 'tag', 'search']),
            'flash' => session('flash'),
        ]);
    }

    public function show(string $slug): InertiaResponse
    {
        $page = WikiPage::where('slug', $slug)
            ->with(['creator:id,name', 'categories:id,name,slug', 'tags:id,name,slug'])
            ->firstOrFail();

        $currentVersion = null;
        if ($page->current_version_id) {
            $currentVersion = WikiVersion::with('creator:id,name')->find($page->current_version_id);
        }

        $canResolveConflict = Auth::check() && Gate::allows('resolve_conflict', $page);
        if ($page->status === WikiPage::STATUS_CONFLICT && ! $canResolveConflict) {
            Log::warning('Access Denied: User (ID: '.Auth::id().") attempted to view conflicted page {$page->id} ('{$page->title}') without resolve permissions.");
            $page->loadMissing('creator:id,name');

            return Inertia::render('Wiki/Conflict', [
                'page' => $page->only('id', 'title', 'slug', 'creator'),
                'message' => '此页面当前存在编辑冲突，需要管理员或指定人员解决。',
                'canResolveConflict' => $canResolveConflict,
            ]);
        }

        if ($page->current_version_id && ! $currentVersion) {
            Log::error("Data Consistency Error: WikiPage ID {$page->id} ('{$page->title}') has current_version_id={$page->current_version_id}, but WikiVersion not found.");
            $page->load(['comments' => function ($query) {
                $query->where('is_hidden', false)
                    ->whereNull('parent_id')
                    ->with(['user:id,name', 'replies' => function ($q) {
                        $q->where('is_hidden', false)->with('user:id,name')->latest('created_at');
                    }])
                    ->latest('created_at');
            }]);

            return Inertia::render('Wiki/Show', [
                'page' => $page,
                'currentVersion' => null,
                'isLocked' => false,
                'lockedBy' => null,
                'draft' => null,
                'canEditPage' => false,
                'canResolveConflict' => $canResolveConflict,
                'error' => '无法加载页面内容，请联系管理员检查数据。',
                'comments' => $page->comments ?? [],
                'flash' => session('flash'),
            ]);
        }

        $page->load(['comments' => function ($query) {
            $query->where('is_hidden', false)
                ->whereNull('parent_id')
                ->with(['user:id,name', 'replies' => function ($q) {
                    $q->where('is_hidden', false)->with('user:id,name')->latest('created_at');
                }])
                ->latest('created_at');
        }]);

        $isLocked = $page->isLocked();
        $lockedBy = null;
        if ($isLocked) {
            $page->loadMissing('locker:id,name');
            $lockedBy = $page->locker;
        }

        $draft = null;
        if (Auth::check()) {
            $draft = WikiPageDraft::where('wiki_page_id', $page->id)
                ->where('user_id', Auth::id())
                ->select('content', 'last_saved_at')
                ->first();
        }

        $canEdit = Auth::check() && ! $isLocked;

        return Inertia::render('Wiki/Show', [
            'page' => $page,
            'currentVersion' => $currentVersion,
            'isLocked' => $isLocked,
            'lockedBy' => $lockedBy ? $lockedBy->only('id', 'name') : null,
            'draft' => $draft,
            'canEditPage' => $canEdit,
            'canResolveConflict' => $canResolveConflict,
            'error' => session('error'),
            'flash' => session('flash'),
            'comments' => $page->comments ?? [],
        ]);
    }

    public function create(): InertiaResponse
    {
        // 使用 Policy
        $this->authorize('create', WikiPage::class);

        $categories = WikiCategory::select('id', 'name')->orderBy('order')->get();
        $tags = WikiTag::select('id', 'name')->get();

        return Inertia::render('Wiki/Create', [
            'categories' => $categories,
            'tags' => $tags,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {

        $this->authorize('create', WikiPage::class);
        $validated = $request->validate([
            'title' => 'required|string|max:255|unique:wiki_pages,title',
            'content' => 'required|string',
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'required|exists:wiki_categories,id',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:wiki_tags,id',
        ]);

        $validated['content'] = Purifier::clean($validated['content']);
        $slug = Str::slug($validated['title']);
        if (empty($slug)) {
            $slug = Pinyin::permalink($validated['title'], '-');
        }
        $originalSlug = $slug;
        $counter = 1;
        while (WikiPage::where('slug', $slug)->exists()) {
            $slug = $originalSlug.'-'.$counter++;
        }

        DB::beginTransaction();
        try {
            $page = WikiPage::create([
                'title' => $validated['title'],
                'slug' => $slug,
                'status' => WikiPage::STATUS_DRAFT,
                'created_by' => Auth::id(),
                'current_version_id' => null,
            ]);

            $version = WikiVersion::create([
                'wiki_page_id' => $page->id,
                'content' => $validated['content'],
                'created_by' => Auth::id(),
                'version_number' => 1,
                'comment' => '初始版本',
                'is_current' => true,
            ]);

            $page->update([
                'current_version_id' => $version->id,
                'status' => WikiPage::STATUS_PUBLISHED,
            ]);

            $page->categories()->attach($validated['category_ids']);
            if (! empty($validated['tag_ids'])) {
                $page->tags()->attach($validated['tag_ids']);
            }

            $this->logActivity('create', $page, ['version' => $version->version_number]);

            DB::commit();
            Log::info("Wiki page {$page->id} ('{$page->title}') created successfully by user ".Auth::id());

            return redirect()->route('wiki.show', $page->slug)
                ->with('flash', ['message' => ['type' => 'success', 'text' => '页面创建成功！']]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating wiki page: '.$e->getMessage());

            return back()->withErrors(['general' => '创建页面时出错，请稍后重试。'])->withInput();
        }
    }

    public function edit(WikiPage $page): InertiaResponse|RedirectResponse
    {
        $this->authorize('update', $page);
        $user = Auth::user();
        $isInConflict = $page->status === WikiPage::STATUS_CONFLICT;
        $canResolveConflict = $user && $user->can('wiki.resolve_conflict');
        $isEditable = Gate::allows('update', $page);
        $draft = WikiPageDraft::where('wiki_page_id', $page->id)
            ->where('user_id', $user->id)
            ->select('content', 'last_saved_at')
            ->orderBy('last_saved_at', 'desc')
            ->first();

        $content = $draft ? $draft->content : ($page->currentVersion ? $page->currentVersion->content : '');

        $page->load(['currentVersion:id,wiki_page_id,version_number', 'categories:id', 'tags:id']);
        $categories = WikiCategory::select('id', 'name')->orderBy('order')->get();
        $tags = WikiTag::select('id', 'name')->orderBy('name')->get();

        app(CollaborationService::class)->registerEditor($page, $user);

        $editPageData = [
            'page' => array_merge(
                $page->only('id', 'title', 'slug', 'current_version_id', 'status'),
                [
                    'category_ids' => $page->categories->pluck('id')->toArray(),
                    'tag_ids' => $page->tags->pluck('id')->toArray(),
                    'current_version' => $page->currentVersion,
                ]
            ),
            'content' => $content,
            'categories' => $categories,
            'tags' => $tags,
            'hasDraft' => ! is_null($draft),
            'lastSaved' => $draft ? $draft->last_saved_at->toIso8601String() : null,
            'canResolveConflict' => $canResolveConflict,
            'isConflict' => $isInConflict,
            'editorIsEditable' => $isEditable,
            'errors' => session('errors') ? session('errors')->getBag('default')->getMessages() : (object) [],
            'flash' => session('flash'),
        ];

        return Inertia::render('Wiki/Edit', $editPageData);
    }

    public function update(Request $request, WikiPage $page): RedirectResponse
    {
        $this->authorize('update', $page);
        $user = Auth::user();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'required|exists:wiki_categories,id',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:wiki_tags,id',
            'comment' => 'nullable|string|max:255',
            'version_id' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) use ($page) {
                    if (! WikiVersion::where('wiki_page_id', $page->id)->where('id', $value)->exists()) {
                        $fail('提交所基于的版本ID无效或不属于此页面。');
                    }
                },
            ],
        ]);

        $newContent = Purifier::clean($validated['content']);
        $submittedBaseVersionId = (int) $validated['version_id'];

        // 重新加载页面以获取最新版本ID，并预加载需要的关联关系
        $page->refresh()->load(['currentVersion', 'categories', 'tags']);
        $currentVersionIdInDb = $page->current_version_id;
        $currentVersionContent = $page->currentVersion?->content ?? ''; // 获取当前数据库版本内容

        // --- 冲突检测逻辑 (保持不变) ---
        if ($page->status !== WikiPage::STATUS_CONFLICT && $currentVersionIdInDb !== $submittedBaseVersionId) {
            Log::info("Potential Conflict Check: User {$user->id} submitted based on version {$submittedBaseVersionId}, but current DB version is {$currentVersionIdInDb} for page {$page->id}. Running DiffService check.");
            $baseVersion = WikiVersion::find($submittedBaseVersionId);
            $currentDbVersion = $page->currentVersion; // 直接使用预加载的关联

            if ($baseVersion && $currentDbVersion) {
                $diffService = app(DiffService::class);
                if ($diffService->hasConflict($baseVersion->content, $currentDbVersion->content, $newContent)) {
                    Log::warning("Conflict DETECTED on page {$page->id} by DiffService. User: {$user->id}, Base: {$submittedBaseVersionId}, Current: {$currentVersionIdInDb}");
                    $latestVersionNumber = $page->versions()->latest('version_number')->value('version_number') ?? 0;

                    // 创建冲突版本 (仅当内容不同时，但逻辑简化为总是创建)
                    WikiVersion::create([
                        'wiki_page_id' => $page->id,
                        'content' => $newContent,
                        'created_by' => $user->id,
                        'version_number' => $latestVersionNumber + 1, // 注意，这里版本号可能需要重新考虑，但根据原逻辑保持
                        'comment' => $validated['comment'] ?: '提交时检测到冲突',
                        'is_current' => false, // 标记为非当前
                    ]);

                    $page->markAsConflict();
                    // 删除草稿并注销编辑者
                    WikiPageDraft::where('wiki_page_id', $page->id)->where('user_id', $user->id)->delete();
                    app(CollaborationService::class)->unregisterEditor($page, $user);

                    return redirect()->route('wiki.show-conflicts', $page->slug)
                        ->with('flash', ['message' => ['type' => 'error', 'text' => '编辑冲突：在您编辑期间，其他用户已修改此页面。您的更改已保存为待处理的冲突版本，请解决冲突。']]);
                } else {
                    Log::info("No actual content conflict detected by DiffService for page {$page->id} despite version mismatch. Proceeding with update based on submitted content.");
                    // 如果没有内容冲突，可以考虑让用户基于他们的版本进行保存，或者强制他们基于最新版（取决于产品逻辑）
                    // 当前逻辑是继续执行保存，这可能覆盖非冲突的并发修改，但简化了流程。
                    // 安全起见，也可以在这里返回错误要求刷新，但当前保持原逻辑。
                }
            } else {
                Log::error("Conflict check failed for page {$page->id}: Base version {$submittedBaseVersionId} or Current DB version {$currentVersionIdInDb} not found.");

                return back()->withErrors(['general' => '无法验证页面版本，请刷新后重试。'])->withInput();
            }
        }
        // --- 冲突检测逻辑结束 ---

        // --- 新增：检查是否有实际更改 ---
        $titleChanged = $validated['title'] !== $page->title;
        $contentChanged = $newContent !== $currentVersionContent;

        $currentCategoryIds = $page->categories->pluck('id')->sort()->values()->toArray();
        $newCategoryIds = collect($validated['category_ids'])->map(fn ($id) => (int) $id)->sort()->values()->toArray();
        $categoriesChanged = $currentCategoryIds != $newCategoryIds;

        $currentTagIds = $page->tags->pluck('id')->sort()->values()->toArray();
        $newTagIds = collect($validated['tag_ids'] ?? [])->map(fn ($id) => (int) $id)->sort()->values()->toArray();
        $tagsChanged = $currentTagIds != $newTagIds;

        $hasChanges = $titleChanged || $contentChanged || $categoriesChanged || $tagsChanged;
        // --- 检查结束 ---

        // 如果没有实际更改
        if (! $hasChanges && $page->status !== WikiPage::STATUS_CONFLICT) { // 同时检查不是在解决冲突
            Log::info("No actual changes detected for page {$page->id} by user {$user->id}. Skipping new version creation.");

            // 即使没有变化，也要删除草稿并注销编辑状态
            WikiPageDraft::where('wiki_page_id', $page->id)->where('user_id', $user->id)->delete();
            app(CollaborationService::class)->unregisterEditor($page, $user);

            // 可以选择不显示 flash 消息，或者显示一个不同的消息
            // return redirect()->route('wiki.show', $page->slug);
            return redirect()->route('wiki.show', $page->slug)
                ->with('flash', ['message' => ['type' => 'info', 'text' => '未检测到更改，页面未更新版本。']]);
            // 或者继续使用成功消息：
            // return redirect()->route('wiki.show', $page->slug)
            //     ->with('flash', ['message' => ['type' => 'success', 'text' => '页面编辑已提交（无内容变更）。']]);
        }

        // --- 如果有更改或正在解决冲突，则执行数据库事务 ---
        DB::beginTransaction();
        try {
            // 处理冲突状态 (如果需要)
            if ($page->status === WikiPage::STATUS_CONFLICT) {
                Log::info("User {$user->id} is resolving conflict for page {$page->id} by saving a new version.");
                $page->markAsResolved($user); // 这会更新 status 和 lock 状态
            }

            // 更新标题 (只有当标题改变时才更新)
            if ($titleChanged) {
                $page->update(['title' => $validated['title']]);
            }

            // --- 版本创建逻辑 ---
            $latestVersionNumber = $page->versions()->lockForUpdate()->latest('version_number')->value('version_number') ?? 0;
            $newVersion = WikiVersion::create([
                'wiki_page_id' => $page->id,
                'content' => $newContent, // 使用清理后的内容
                'created_by' => $user->id,
                'version_number' => $latestVersionNumber + 1,
                'comment' => $validated['comment'] ?: ($page->status === WikiPage::STATUS_CONFLICT ? '解决冲突' : '更新页面'), // 根据状态调整默认注释
                'is_current' => true,
            ]);

            // 更新旧的当前版本
            $previousCurrentVersionId = $page->current_version_id;
            if ($previousCurrentVersionId && $previousCurrentVersionId !== $newVersion->id) {
                WikiVersion::where('id', $previousCurrentVersionId)->update(['is_current' => false]);
            }

            // 更新页面的当前版本ID (status 已在 markAsResolved 中处理或保持 published)
            $page->update(['current_version_id' => $newVersion->id]);
            // --- 版本创建逻辑结束 ---

            // 同步分类和标签 (只有改变时才同步，减少数据库操作)
            if ($categoriesChanged) {
                $page->categories()->sync($validated['category_ids']);
            }
            if ($tagsChanged) {
                $page->tags()->sync($validated['tag_ids'] ?? []);
            }

            // 删除草稿并注销编辑状态
            WikiPageDraft::where('wiki_page_id', $page->id)->where('user_id', $user->id)->delete();
            app(CollaborationService::class)->unregisterEditor($page, $user);

            // 记录活动日志
            $logProperties = ['version' => $newVersion->version_number];
            if ($titleChanged) {
                $logProperties['title_changed'] = true;
            }
            if ($contentChanged) {
                $logProperties['content_changed'] = true;
            }
            if ($categoriesChanged) {
                $logProperties['categories_changed'] = true;
            }
            if ($tagsChanged) {
                $logProperties['tags_changed'] = true;
            }
            $this->logActivity('update', $page, $logProperties);

            DB::commit();
            Log::info("Page {$page->id} updated successfully to version {$newVersion->version_number} by user {$user->id}.");

            // 广播事件
            try {
                event(new WikiPageVersionUpdated($page->id, $newVersion->id));
                Log::info("Broadcasted WikiPageVersionUpdated event for page {$page->id}, new version ID: {$newVersion->id}");
            } catch (\Exception $e) {
                Log::error("Failed to broadcast WikiPageVersionUpdated event for page {$page->id}: ".$e->getMessage());
            }

            // 重定向
            return redirect()->route('wiki.show', $page->slug)
                ->with('flash', ['message' => ['type' => 'success', 'text' => '页面更新成功！']]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error updating page {$page->id} by user {$user->id}: ".$e->getMessage()."\n".$e->getTraceAsString());

            return back()->withErrors(['general' => '保存页面时发生内部错误，请稍后重试。'])->withInput();
        }
    }

    public function saveDraft(Request $request, $pageId): JsonResponse
    {
        try {
            // 使用 findOrFail 获取页面，确保它存在且未被软删除
            $page = WikiPage::findOrFail((int) $pageId);
        } catch (ModelNotFoundException $e) {
            Log::warning("Attempted to save draft for non-existent or deleted page ID: {$pageId}");

            return response()->json(['message' => '页面不存在或已被删除'], SymfonyResponse::HTTP_NOT_FOUND);
        }

        $user = Auth::user();

        if ($page->status === WikiPage::STATUS_CONFLICT) {
            Log::warning("Draft save denied for page {$page->id} by user {$user->id}. Reason: Page is in conflict status.");

            return response()->json(['message' => '页面处于冲突状态，无法保存草稿'], SymfonyResponse::HTTP_CONFLICT);
        }

        $validated = $request->validate(['content' => 'required|string']);

        try {
            $draft = WikiPageDraft::updateOrCreate(
                ['wiki_page_id' => $page->id, 'user_id' => $user->id],
                [
                    'content' => Purifier::clean($validated['content']),
                    'last_saved_at' => now(),
                ]
            );

            Log::info("Draft saved for page {$page->id} by user {$user->id}. Draft ID: {$draft->id}");

            return response()->json([
                'message' => '草稿已自动保存',
                'saved_at' => $draft->last_saved_at->toIso8601String(),
                'draft_id' => $draft->id,
                'success' => true,
            ]);
        } catch (\Exception $e) {
            Log::error("Error saving draft for page {$page->id} by user {$user->id}: ".$e->getMessage());

            return response()->json(['message' => '保存草稿时出错', 'success' => false], SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(WikiPage $page): RedirectResponse
    {
        // 注意：这里不再需要 DB::beginTransaction() 和 commit/rollback
        // 因为软删除通常是一个原子操作，除非你有非常复杂的关联删除逻辑
        $this->authorize('delete', $page);
        try {
            $pageTitle = $page->title;
            $pageId = $page->id;
            $userId = Auth::id();

            // 使用 delete() 方法触发软删除
            if ($page->delete()) { // delete() 返回 true 或 false
                $this->logActivity('delete', $page, ['title' => $pageTitle, 'soft_deleted' => true]); // 标记为软删除
                Log::info("Wiki page {$pageId} ('{$pageTitle}') soft deleted by user {$userId}.");

                return redirect()->route('wiki.index')
                    ->with('flash', ['message' => ['type' => 'success', 'text' => '页面已移至回收站！']]);
            } else {
                Log::error("Failed to soft delete page {$pageId} ('{$pageTitle}') by user {$userId}.");

                return back()->withErrors(['general' => '将页面移至回收站时出错，请稍后重试。']);
            }
        } catch (\Exception $e) {
            Log::error("Error soft deleting page {$page->id}: ".$e->getMessage());

            return back()->withErrors(['general' => '删除页面时发生内部错误，请稍后重试。']);
        }
    }

    /**
     * 显示回收站中的页面列表.
     */
    public function trashIndex(Request $request): InertiaResponse
    {
        $this->authorize('viewTrash', WikiPage::class);

        $query = WikiPage::onlyTrashed() // 只查询软删除的页面
            ->with(['creator:id,name', 'categories:id,name,slug', 'tags:id,name,slug']);

        // 可以添加搜索功能
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $trashedPages = $query->latest('deleted_at') // 按删除时间排序
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Wiki/Trash/Index', [
            'trashedPages' => $trashedPages,
            'filters' => $request->only(['search']),
            'flash' => session('flash'),
        ]);
    }

    /**
     * 从回收站恢复页面.
     * 使用 ID 而不是 slug，因为 slug 可能不唯一（如果原 slug 被重用）
     */
    public function restore(int $pageId): RedirectResponse
    {
        $this->authorize('restore', WikiPage::class);

        try {
            $page = WikiPage::onlyTrashed()->findOrFail($pageId);
            $this->authorize('restore', $page); // 使用实例检查更精确
            $page->restore(); // 恢复页面

            $this->logActivity('restore', $page);
            Log::info("Wiki page {$page->id} ('{$page->title}') restored from trash by user ".Auth::id());

            return redirect()->route('wiki.trash.index')
                ->with('flash', ['message' => ['type' => 'success', 'text' => '页面已成功恢复！']]);
        } catch (ModelNotFoundException $e) {
            Log::warning("Attempted to restore non-existent or not-trashed page ID: {$pageId}");

            return redirect()->route('wiki.trash.index')
                ->withErrors(['general' => '无法找到要恢复的页面。']);
        } catch (\Exception $e) {
            Log::error("Error restoring page {$pageId} from trash: ".$e->getMessage());

            return redirect()->route('wiki.trash.index')
                ->withErrors(['general' => '恢复页面时出错，请稍后重试。']);
        }
    }

    /**
     * 永久删除页面.
     * 使用 ID
     */
    public function forceDelete(int $pageId): RedirectResponse
    {
        $this->authorize('forceDelete', WikiPage::class);

        try {
            $page = WikiPage::onlyTrashed()->findOrFail($pageId);
            $this->authorize('forceDelete', $page);
            $pageTitle = $page->title;

            DB::beginTransaction();
            try {
                // 在这里可以添加删除关联数据的逻辑，如果需要的话
                // 例如：$page->versions()->delete(); $page->comments()->delete(); 等
                // 注意：如果设置了外键约束的 onDelete('cascade')，则不需要手动删除关联数据

                $page->forceDelete(); // 永久删除

                $this->logActivity('force_delete', $page, ['title' => $pageTitle]);
                Log::info("Wiki page {$pageId} ('{$pageTitle}') permanently deleted by user ".Auth::id());

                DB::commit();

                return redirect()->route('wiki.trash.index')
                    ->with('flash', ['message' => ['type' => 'success', 'text' => '页面已永久删除！']]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e; // 重新抛出异常以便外部捕获
            }
        } catch (ModelNotFoundException $e) {
            Log::warning("Attempted to force delete non-existent or not-trashed page ID: {$pageId}");

            return redirect()->route('wiki.trash.index')
                ->withErrors(['general' => '无法找到要永久删除的页面。']);
        } catch (\Exception $e) {
            Log::error("Error force deleting page {$pageId}: ".$e->getMessage());

            return redirect()->route('wiki.trash.index')
                ->withErrors(['general' => '永久删除页面时出错，请稍后重试。']);
        }
    }

    public function showVersion(WikiPage $page, int $versionNumber): InertiaResponse
    {
        $this->authorize('viewHistory', $page); // 添加检查
        $versionRecord = WikiVersion::where('wiki_page_id', $page->id)
            ->where('version_number', $versionNumber)
            ->with('creator:id,name')
            ->firstOrFail();

        $page->load(['creator:id,name', 'categories:id,name,slug', 'tags:id,name,slug']);

        return Inertia::render('Wiki/ShowVersion', [
            'page' => $page->only('id', 'title', 'slug', 'creator', 'categories', 'tags'),
            'version' => $versionRecord->only('id', 'version_number', 'content', 'comment', 'created_at', 'creator'),
        ]);
    }

    public function history(WikiPage $page): InertiaResponse
    {
        $this->authorize('viewHistory', $page);
        $versions = WikiVersion::where('wiki_page_id', $page->id)
            ->with('creator:id,name')
            ->orderBy('version_number', 'desc')
            ->select('id', 'version_number', 'comment', 'created_at', 'created_by', 'is_current')
            ->paginate(15);

        return Inertia::render('Wiki/History', [
            'page' => $page->only('id', 'title', 'slug'),
            'versions' => $versions,
        ]);
    }

    public function compareVersions(WikiPage $page, int $fromVersionNumber, int $toVersionNumber): InertiaResponse|RedirectResponse
    {
        $this->authorize('viewHistory', $page);
        if ($fromVersionNumber === $toVersionNumber) {
            return redirect()->route('wiki.history', $page->slug)
                ->with('flash', ['message' => ['type' => 'warning', 'text' => '请选择两个不同的版本进行比较。']]);
        }

        $fromVersion = WikiVersion::where('wiki_page_id', $page->id)
            ->where('version_number', $fromVersionNumber)
            ->with('creator:id,name')
            ->firstOrFail();

        $toVersion = WikiVersion::where('wiki_page_id', $page->id)
            ->where('version_number', $toVersionNumber)
            ->with('creator:id,name')
            ->firstOrFail();

        $diffService = app(DiffService::class);
        $diffHtml = $diffService->generateDiffHtml($fromVersion->content, $toVersion->content);

        $olderVersion = $fromVersion->version_number < $toVersion->version_number ? $fromVersion : $toVersion;
        $newerVersion = $fromVersion->version_number > $toVersion->version_number ? $fromVersion : $toVersion;

        return Inertia::render('Wiki/Compare', [
            'page' => $page->only('id', 'title', 'slug'),
            'fromVersion' => $olderVersion->only('id', 'version_number', 'content', 'comment', 'created_at', 'creator'),
            'toVersion' => $newerVersion->only('id', 'version_number', 'content', 'comment', 'created_at', 'creator'),
            'fromCreator' => $olderVersion->creator,
            'toCreator' => $newerVersion->creator,
            'diffHtml' => $diffHtml,
        ]);
    }

    public function revertToVersion(Request $request, WikiPage $page, int $versionNumberToRevertTo): RedirectResponse
    {
        $this->authorize('revert', $page);
        $user = Auth::user();

        $versionToRevert = WikiVersion::where('wiki_page_id', $page->id)
            ->where('version_number', $versionNumberToRevertTo)
            ->firstOrFail();

        if ($page->status === WikiPage::STATUS_CONFLICT) {
            return back()->withErrors(['general' => '无法恢复版本，页面当前处于冲突状态，请先解决冲突。']);
        }

        DB::beginTransaction();
        try {
            $currentVersionIdInDb = $page->current_version_id;
            $latestVersionNumber = $page->versions()->latest('version_number')->value('version_number') ?? 0;

            $newVersion = WikiVersion::create([
                'wiki_page_id' => $page->id,
                'content' => $versionToRevert->content,
                'created_by' => $user->id,
                'version_number' => $latestVersionNumber + 1,
                'comment' => '恢复自版本 '.$versionNumberToRevertTo,
                'is_current' => true,
            ]);

            if ($currentVersionIdInDb) {
                WikiVersion::where('id', $currentVersionIdInDb)->update(['is_current' => false]);
            }
            $page->update(['current_version_id' => $newVersion->id]);

            $this->logActivity('revert', $page, [
                'reverted_to_version' => $versionNumberToRevertTo,
                'new_version' => $newVersion->version_number,
            ]);

            DB::commit();
            Log::info("Page {$page->id} reverted to version {$versionNumberToRevertTo} by user {$user->id}. New version: {$newVersion->version_number}");

            return redirect()->route('wiki.show', $page->slug)
                ->with('flash', ['message' => ['type' => 'success', 'text' => '页面已恢复到版本 '.$versionNumberToRevertTo]]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error reverting page {$page->id} to version {$versionNumberToRevertTo} by user {$user->id}: ".$e->getMessage());

            return back()->withErrors(['general' => '恢复版本时出错，请稍后重试。']);
        }
    }

    public function showConflicts(WikiPage $page, DiffService $diffService): InertiaResponse|RedirectResponse
    {
        $this->authorize('resolveConflict', $page);
        if ($page->status !== WikiPage::STATUS_CONFLICT) {
            Log::info("Attempted to show conflicts for page {$page->id} which is not in conflict.");

            return redirect()->route('wiki.show', $page->slug)
                ->with('flash', ['message' => ['type' => 'info', 'text' => '该页面当前没有冲突需要解决。']]);
        }

        $currentVersion = $page->currentVersion()->with('creator:id,name')->first();
        $conflictingVersion = $page->versions()
            ->where('is_current', false)
            ->orderBy('version_number', 'desc')
            ->with('creator:id,name')
            ->first();

        if (! $currentVersion || ! $conflictingVersion) {
            Log::error("Conflict resolution error for page {$page->id}: Missing current or conflicting version data.");

            return redirect()->route('wiki.show', $page->slug)
                ->withErrors(['general' => '无法加载冲突版本信息，请联系管理员。']);
        }

        $diffHtml = $diffService->generateDiffHtml($conflictingVersion->content, $currentVersion->content);

        return Inertia::render('Wiki/ShowConflicts', [
            'page' => $page->only('id', 'title', 'slug'),
            'conflictVersions' => [
                'current' => $currentVersion->only('id', 'version_number', 'content', 'comment', 'created_at', 'creator'),
                'conflict' => $conflictingVersion->only('id', 'version_number', 'content', 'comment', 'created_at', 'creator'),
            ],
            'diffHtml' => $diffHtml,
            'flash' => session('flash'),
        ]);
    }

    public function resolveConflict(Request $request, WikiPage $page): RedirectResponse
    {
        $this->authorize('resolveConflict', $page);
        $user = Auth::user();

        if ($page->status !== WikiPage::STATUS_CONFLICT) {
            Log::info("Conflict resolution submitted for page {$page->id} which is not in conflict status. Redirecting.");

            return redirect()->route('wiki.show', $page->slug)
                ->with('flash', ['message' => ['type' => 'info', 'text' => '该页面当前没有冲突需要解决。']]);
        }

        $validated = $request->validate([
            'content' => 'required|string',
            'resolution_comment' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $cleanContent = Purifier::clean($validated['content']);
            $latestVersionNumber = $page->versions()->where('wiki_page_id', $page->id)->latest('version_number')->value('version_number') ?? 0;

            $newVersion = WikiVersion::create([
                'wiki_page_id' => $page->id,
                'content' => $cleanContent,
                'created_by' => $user->id,
                'version_number' => $latestVersionNumber + 1,
                'comment' => $validated['resolution_comment'] ?: '解决编辑冲突',
                'is_current' => true,
            ]);

            WikiVersion::where('wiki_page_id', $page->id)
                ->where('id', '!=', $newVersion->id)
                ->update(['is_current' => false]);

            $page->update(['current_version_id' => $newVersion->id]);
            $page->markAsResolved($user);

            if (method_exists($page, 'logCustomActivity')) {
                $page->logCustomActivity('conflict_resolved', ['resolved_by' => $user->id, 'new_version' => $newVersion->version_number]);
            } else {
                ActivityLog::log('conflict_resolved', $page, ['resolved_by' => $user->id, 'new_version' => $newVersion->version_number]);
            }

            DB::commit();
            Log::info("Conflict for page {$page->id} resolved by user {$user->id}. New current version: {$newVersion->version_number}");

            return redirect()->route('wiki.show', $page->slug)
                ->with('flash', ['message' => ['type' => 'success', 'text' => '页面冲突已成功解决！']]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error resolving conflict for page {$page->id} by user {$user->id}: ".$e->getMessage()."\n".$e->getTraceAsString());

            return back()->withErrors(['general' => '解决冲突时发生内部错误，请稍后重试。'])->withInput();
        }
    }

    public function getEditors(WikiPage $page, CollaborationService $collaborationService): JsonResponse
    {
        $editors = $collaborationService->getEditors($page->id);

        return response()->json(['editors' => array_values($editors)]);
    }

    public function registerEditor(Request $request, WikiPage $page, CollaborationService $collaborationService): JsonResponse
    {
        $user = Auth::user();
        $collaborationService->registerEditor($page, $user);

        return response()->json(['success' => true, 'message' => '已注册为页面编辑者或心跳已更新']);
    }

    public function unregisterEditor(Request $request, WikiPage $page, CollaborationService $collaborationService): JsonResponse
    {
        $user = Auth::user();
        $collaborationService->unregisterEditor($page, $user);

        return response()->json(['success' => true, 'message' => '已注销编辑状态']);
    }

    public function getDiscussionMessages(WikiPage $page, CollaborationService $collaborationService): JsonResponse
    {
        $messages = $collaborationService->getDiscussionMessages($page->id);

        return response()->json(['messages' => $messages]);
    }

    public function sendDiscussionMessage(Request $request, WikiPage $page, CollaborationService $collaborationService): JsonResponse
    {
        $validated = $request->validate(['message' => 'required|string|max:500']);
        $user = Auth::user();

        try {
            $message = $collaborationService->addDiscussionMessage($page, $user, $validated['message']);

            return response()->json(['success' => true, 'message' => $message]);
        } catch (\Exception $e) {
            Log::error("Error sending discussion message for page {$page->id} by user {$user->id}: ".$e->getMessage());

            return response()->json(['success' => false, 'message' => '发送消息失败'], SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    protected function logActivity(string $action, Model $subject, ?array $properties = null): void
    {
        if (app()->runningInConsole()) {
            return;
        }

        try {
            ActivityLog::log($action, $subject, $properties);
        } catch (\Exception $e) {
            Log::error("Failed to log activity: Action={$action}, Subject={$subject->getTable()}:{$subject->getKey()}, Error: ".$e->getMessage());
        }
    }

    public function preview(Request $request): InertiaResponse
    {
        // 不需要检查权限，因为这是用户正在编辑内容的预览
        $user = Auth::user();

        // 1. 验证输入 - 基本检查，确保核心字段存在
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:wiki_categories,id',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'integer|exists:wiki_tags,id',
        ]);

        // 2. 清理内容 - *非常重要*
        $cleanContent = Purifier::clean($validated['content']);

        // 3. 模拟页面和版本数据结构
        $categories = WikiCategory::whereIn('id', $validated['category_ids'] ?? [])->select('id', 'name', 'slug')->get();
        $tags = WikiTag::whereIn('id', $validated['tag_ids'] ?? [])->select('id', 'name', 'slug')->get();

        // 使用 stdClass 创建一个类似 Page 的对象
        $pseudoPage = new \stdClass;
        $pseudoPage->id = 0; // 表示未保存
        $pseudoPage->title = $validated['title'];
        $pseudoPage->slug = 'preview'; // Slug for preview doesn't matter much
        $pseudoPage->creator = $user ? $user->only('id', 'name') : ['id' => 0, 'name' => '预览用户'];
        $pseudoPage->created_at = now();
        $pseudoPage->updated_at = now();
        $pseudoPage->categories = $categories->map(fn ($cat) => $cat->toArray())->toArray(); // Ensure arrays
        $pseudoPage->tags = $tags->map(fn ($tag) => $tag->toArray())->toArray(); // Ensure arrays
        $pseudoPage->status = 'preview'; // Custom status

        // 使用 stdClass 创建一个类似 Version 的对象
        $pseudoVersion = new \stdClass;
        $pseudoVersion->id = 0; // 表示未保存
        $pseudoVersion->content = $cleanContent;
        $pseudoVersion->creator = $pseudoPage->creator;
        $pseudoVersion->created_at = now();
        $pseudoVersion->version_number = '预览';
        $pseudoVersion->comment = '实时预览内容';
        $pseudoVersion->is_current = true;

        // 4. 渲染 Show.vue 组件，传入模拟数据
        return Inertia::render('Wiki/Show', [
            'page' => (array) $pseudoPage,             // 确保是数组
            'currentVersion' => (array) $pseudoVersion, // 确保是数组
            'isLocked' => false,                      // Preview is never locked
            'lockedBy' => null,
            'draft' => null,                          // No draft concept in pure preview
            'canEditPage' => false,                   // Cannot edit a preview view
            'canResolveConflict' => false,            // No conflict in preview
            'error' => null,
            'comments' => [],                         // No comments in preview
            'flash' => null,
            'isPreview' => true,                      // Flag to indicate preview mode
        ]);
    }
}

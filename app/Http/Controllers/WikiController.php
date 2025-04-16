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
            // 加载版本的同时，也加载其创建者信息
            $currentVersion = WikiVersion::with('creator:id,name')->find($page->current_version_id);
        }

        // 检查权限：是否能解决冲突
        $canResolveConflict = Auth::check() && Gate::allows('resolveConflict', $page);

        // 如果页面处于冲突状态，并且用户不能解决冲突
        if ($page->status === WikiPage::STATUS_CONFLICT && !$canResolveConflict) {
            Log::warning('Access Denied: User (ID: ' . Auth::id() . ") attempted to view conflicted page {$page->id} ('{$page->title}') without resolve permissions.");
            $page->loadMissing('creator:id,name'); // 确保创建者信息已加载
            return Inertia::render('Wiki/Conflict', [
                'page' => $page->only('id', 'title', 'slug', 'creator'),
                'message' => '此页面当前存在编辑冲突，需要管理员或指定人员解决。',
                'canResolveConflict' => $canResolveConflict,
            ]);
        }

        // 如果页面记录了当前版本ID，但找不到对应的版本记录（数据不一致）
        if ($page->current_version_id && !$currentVersion) {
            Log::error("Data Consistency Error: WikiPage ID {$page->id} ('{$page->title}') has current_version_id={$page->current_version_id}, but WikiVersion not found.");
            // 仍然加载评论等信息
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
                'currentVersion' => null, // 明确告知前端版本加载失败
                'isLocked' => false, // 不确定锁定状态，保守设为false
                'lockedBy' => null,
                'draft' => null,
                'canEditPage' => false, // 数据不一致时不允许编辑
                'canResolveConflict' => $canResolveConflict,
                'error' => '无法加载页面内容，请联系管理员检查数据。', // 向用户显示错误信息
                'comments' => $page->comments ?? [],
                'flash' => session('flash'),
            ]);
        }

        // 加载评论，注意条件：未隐藏且是顶级评论，并预加载用户信息和回复
        $page->load(['comments' => function ($query) {
            $query->where('is_hidden', false)
                ->whereNull('parent_id') // 只加载顶级评论
                ->with(['user:id,name', 'replies' => function ($q) {
                    $q->where('is_hidden', false)->with('user:id,name')->latest('created_at'); // 加载未隐藏的回复及其用户信息
                }])
                ->latest('created_at');
        }]);

        // 检查页面是否被锁定
        $isLocked = $page->isLocked();
        $lockedBy = null;
        if ($isLocked) {
            // 如果锁定，加载锁定者的信息
            $page->loadMissing('locker:id,name');
            $lockedBy = $page->locker;
        }

        // 检查当前用户是否有草稿
        $draft = null;
        if (Auth::check()) {
            $draft = WikiPageDraft::where('wiki_page_id', $page->id)
                ->where('user_id', Auth::id())
                ->select('content', 'last_saved_at')
                ->first();
        }

        // 用户能否编辑此页面
        $canEdit = Auth::check() && !$isLocked && $page->status !== WikiPage::STATUS_CONFLICT;

        return Inertia::render('Wiki/Show', [
            'page' => $page,
            'currentVersion' => $currentVersion,
            'isLocked' => $isLocked,
            'lockedBy' => $lockedBy ? $lockedBy->only('id', 'name') : null, // 只传递必要信息
            'draft' => $draft,
            'canEditPage' => $canEdit,
            'canResolveConflict' => $canResolveConflict,
            'error' => session('error'), // 从 Session 获取可能的错误信息
            'flash' => session('flash'), // 从 Session 获取闪存消息
            'comments' => $page->comments ?? [], // 确保 comments 总是数组
            'isPreview' => false, // 这不是预览模式
        ]);
    }

    public function create(): InertiaResponse
    {
        $this->authorize('create', WikiPage::class); // 检查创建权限

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
            'category_ids' => 'required|array|min:1', // 至少需要一个分类
            'category_ids.*' => 'required|exists:wiki_categories,id', // 验证每个分类ID存在
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:wiki_tags,id', // 验证每个标签ID存在
        ]);

        // 清理 HTML 内容
        $validated['content'] = Purifier::clean($validated['content']);

        // 生成 Slug
        $slug = Str::slug($validated['title']);
        if (empty($slug)) {
            // 如果标题只包含非拉丁字符，使用拼音生成
            $slug = Pinyin::permalink($validated['title'], '-');
        }

        // 处理 Slug 冲突
        $originalSlug = $slug;
        $counter = 1;
        while (WikiPage::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter++;
        }

        DB::beginTransaction();
        try {
            // 1. 创建页面记录 (初始状态为草稿)
            $page = WikiPage::create([
                'title' => $validated['title'],
                'slug' => $slug,
                'status' => WikiPage::STATUS_DRAFT, // 先设为草稿
                'created_by' => Auth::id(),
                'current_version_id' => null, // 初始没有版本
            ]);

            // 2. 创建第一个版本记录
            $version = WikiVersion::create([
                'wiki_page_id' => $page->id,
                'content' => $validated['content'],
                'created_by' => Auth::id(),
                'version_number' => 1,
                'comment' => '初始版本',
                'is_current' => true,
            ]);

            // 3. 更新页面记录，关联到新创建的版本，并将状态设为已发布
            $page->update([
                'current_version_id' => $version->id,
                'status' => WikiPage::STATUS_PUBLISHED,
            ]);

            // 4. 关联分类和标签
            $page->categories()->attach($validated['category_ids']);
            if (!empty($validated['tag_ids'])) {
                $page->tags()->attach($validated['tag_ids']);
            }

            // 5. 记录活动日志
            $this->logActivity('create', $page, ['version' => $version->version_number]);

            DB::commit(); // 提交事务

            Log::info("Wiki page {$page->id} ('{$page->title}') created successfully by user " . Auth::id());

            return redirect()->route('wiki.show', $page->slug)
                ->with('flash', ['message' => ['type' => 'success', 'text' => '页面创建成功！']]);
        } catch (\Exception $e) {
            DB::rollBack(); // 回滚事务
            Log::error('Error creating wiki page: ' . $e->getMessage());
            return back()->withErrors(['general' => '创建页面时出错，请稍后重试。'])->withInput();
        }
    }

    public function edit(WikiPage $page, CollaborationService $collaborationService): InertiaResponse|RedirectResponse
    {
        $this->authorize('update', $page); // 先检查是否有更新权限 (策略会处理锁定和冲突基础逻辑)
        $user = Auth::user();
        // 检查并发编辑限制
        $editors = $collaborationService->getEditors($page->id);
        $editorCount = count($editors);
        $isCurrentUserEditing = isset($editors[$user->id]);
        if ($editorCount >= 2 && !$isCurrentUserEditing) {
            Log::warning("Editor limit reached for page {$page->id}. User {$user->id} denied entry.");
            return redirect()->route('wiki.show', $page->slug)
                ->with('flash', ['message' => ['type' => 'error', 'text' => '此页面当前已有两人正在编辑，请稍后再试。']]);
        }
        // 检查冲突状态和权限
        $isInConflict = $page->status === WikiPage::STATUS_CONFLICT;
        $canResolveConflict = $user && $user->can('wiki.resolve_conflict');
        if ($isInConflict && !$canResolveConflict) {
            Log::warning("User {$user->id} attempted to edit conflicted page {$page->id} without resolve permissions.");
            return redirect()->route('wiki.show', $page->slug)
                ->with('flash', ['message' => ['type' => 'error', 'text' => '此页面处于冲突状态，需要权限解决后才能编辑。']]);
        }
        // 使用 Gate::allows 检查是否允许编辑 (它内部会调用 Policy 的 update 方法)
        $isEditable = Gate::allows('update', $page);
        // 如果不可编辑且不是冲突待解决状态（即被他人锁定），则重定向
        if (!$isEditable && !$isInConflict) {
            $page->loadMissing('locker:id,name'); // 加载锁定者信息
            $lockerName = $page->locker ? $page->locker->name : '未知用户';
            Log::warning("User {$user->id} attempted to edit locked page {$page->id}. Locked by {$lockerName}.");
            return redirect()->route('wiki.show', $page->slug)
                ->with('flash', ['message' => ['type' => 'warning', 'text' => "页面当前被 {$lockerName} 锁定编辑中。"]]);
        }
        // 获取用户草稿或最新版本内容
        $draft = WikiPageDraft::where('wiki_page_id', $page->id)
            ->where('user_id', $user->id)
            ->select('content', 'last_saved_at')
            ->orderBy('last_saved_at', 'desc')
            ->first();
        $content = $draft ? $draft->content : ($page->currentVersion ? $page->currentVersion->content : '');
        // 加载必要的数据
        $page->load(['currentVersion:id,wiki_page_id,version_number', 'categories:id', 'tags:id']);
        $categories = WikiCategory::select('id', 'name')->orderBy('order')->get();
        $tags = WikiTag::select('id', 'name')->orderBy('name')->get();
        // 如果允许编辑或可以解决冲突，则注册编辑者
        $editorIsEffectivelyEditable = $isEditable || ($isInConflict && $canResolveConflict);
        if ($editorIsEffectivelyEditable) {
            $collaborationService->registerEditor($page, $user);
        }
        // 准备传递给视图的数据
        $editPageData = [
            'page' => array_merge(
                $page->only('id', 'title', 'slug', 'current_version_id', 'status'),
                [
                    'category_ids' => $page->categories->pluck('id')->toArray(),
                    'tag_ids' => $page->tags->pluck('id')->toArray(),
                    // 注意: currentVersion 可能为 null
                    'current_version' => $page->currentVersion,
                ]
            ),
            'content' => $content,
            'categories' => $categories,
            'tags' => $tags,
            'hasDraft' => !is_null($draft),
            'lastSaved' => $draft ? $draft->last_saved_at->toIso8601String() : null,
            'canResolveConflict' => $canResolveConflict,
            'isConflict' => $isInConflict,
            'editorIsEditable' => $editorIsEffectivelyEditable, // 传递实际可编辑状态给前端
            'errors' => session('errors') ? session('errors')->getBag('default')->getMessages() : (object) [],
            'flash' => session('flash'),
            // 将初始版本信息传递给前端，用于提交时的版本校验
            'initialVersionId' => $page->current_version_id,
            'initialVersionNumber' => $page->currentVersion?->version_number ?? 0, // 如果没有版本则为0
        ];
        return Inertia::render('Wiki/Edit', $editPageData);
    }

    // AJAX 保存草稿
    public function saveDraft(Request $request, $pageId): JsonResponse
    {
        try {
            $page = WikiPage::findOrFail((int)$pageId); // 确保页面存在
        } catch (ModelNotFoundException $e) {
            Log::warning("Attempted to save draft for non-existent or deleted page ID: {$pageId}");
            return response()->json(['message' => '页面不存在或已被删除'], SymfonyResponse::HTTP_NOT_FOUND);
        }

        $user = Auth::user();

        // 如果页面处于冲突状态，不允许保存草稿
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
                    'last_saved_at' => now()
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
            Log::error("Error saving draft for page {$page->id} by user {$user->id}: " . $e->getMessage());
            return response()->json(['message' => '保存草稿时出错', 'success' => false], SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // 更新 Wiki 页面
    public function update(Request $request, WikiPage $page, CollaborationService $collaborationService, DiffService $diffService): JsonResponse | RedirectResponse
    {
        // 权限检查
        $this->authorize('update', $page);
        $user = Auth::user();

        // 记录收到的基础版本ID，用于调试
        $submittedBaseVersionIdInput = $request->input('version_id');
        Log::info("WikiController@update: Page ID {$page->id}, User ID {$user->id}. Received base version ID input: {$submittedBaseVersionIdInput}.");

        // 验证请求数据
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'required|exists:wiki_categories,id',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:wiki_tags,id',
            'comment' => 'nullable|string|max:255',
            'version_id' => [ // 客户端提交的，基于此版本进行的编辑
                'required',
                'integer',
                // 可选：进一步验证version_id确实属于此页面
                function ($attribute, $value, $fail) use ($page) {
                    if ($value !== null && !WikiVersion::where('wiki_page_id', $page->id)->where('id', $value)->exists()) {
                        // 注意：初次创建可能 version_id 为 null 或 0，允许这种情况
                        if ($value === 0 || $value === null) {
                            // Allow 0 or null if the page might be new or has no versions yet?
                            // Let's assume 0 means "based on nothing/new", null might be an error from client
                            if ($value !== null && !$page->wasRecentlyCreated && $page->versions()->count() > 0) {
                                $fail('无效的基础版本ID。');
                            }
                        } else {
                            $fail('提交所基于的版本ID无效或不属于此页面。');
                        }
                    }
                },
            ],
            'force_conflict' => 'sometimes|boolean', // 是否强制提交为冲突
            'check_only' => 'sometimes|boolean' // 新增：仅检查版本是否过时，不保存
        ]);

        // 如果只是检查版本
        if ($request->boolean('check_only')) {
            $page->refresh(); // 确保获取最新的页面信息
            if ($page->current_version_id != $validated['version_id']) {
                // 版本已过时
                return response()->json(['status' => 'stale_version', 'current_version_id' => $page->current_version_id], 409);
            }
            // 版本未过时
            return response()->json(['status' => 'ok']);
        }


        // 清理和准备数据
        $newContent = Purifier::clean($validated['content']);
        $submittedBaseVersionId = (int) $validated['version_id']; // 用户基于的版本ID
        $forceConflict = $request->boolean('force_conflict', false); // 从 Modal 提交强制冲突的标记

        // 重新加载页面和当前版本信息，防止竞态条件
        // $page->refresh()->load(['currentVersion', 'categories', 'tags']);
        $page = WikiPage::with(['currentVersion.creator:id,name', 'categories:id', 'tags:id'])->findOrFail($page->id); // 使用 findOrFail 并加载关联数据
        $currentVersionIdInDb = $page->current_version_id; // 数据库中最新的版本ID
        $currentDbVersion = $page->currentVersion; // 数据库中最新的版本实例

        Log::info("WikiController@update: Page ID {$page->id}. DB current version ID: {$currentVersionIdInDb}. Submitted base ID: {$submittedBaseVersionId}. Force conflict: " . ($forceConflict ? 'true' : 'false'));


        // *** 核心冲突检测逻辑 ***
        if ($currentVersionIdInDb !== $submittedBaseVersionId && !$forceConflict) {
            Log::warning("Stale version detected for page {$page->id}. User base: {$submittedBaseVersionId}, DB current: {$currentVersionIdInDb}. User: {$user->id}");

            // 尝试获取用户提交所基于的版本
            $baseVersion = WikiVersion::find($submittedBaseVersionId);

            // 检查必要版本是否存在，以生成 diff
            if (!$baseVersion || !$currentDbVersion) {
                Log::error("Cannot generate diffs for stale check: Base version {$submittedBaseVersionId} or Current DB version {$currentVersionIdInDb} not found for page {$page->id}.");
                // 如果缺少版本信息，返回一个通用错误，提示用户刷新
                return response()->json([
                    'status' => 'error', // 或者 'missing_data'
                    'message' => '无法加载用于比较的版本信息，数据可能已发生变化，请刷新页面后重试。',
                    'current_version_id' => $currentVersionIdInDb, // 仍然告知当前的ID
                ], SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR); // 或者 404 / 409
            }


            // 生成差异信息
            $diffBaseVsCurrent = $diffService->generateDiffHtml($baseVersion->content, $currentDbVersion->content);
            $diffUserVsCurrent = $diffService->generateDiffHtml($newContent, $currentDbVersion->content);


            // 返回 409 Conflict 响应，包含必要信息给前端模态框
            return response()->json([
                'status' => 'stale_version',
                'message' => '页面已被其他用户更新。请处理版本差异。',
                'current_version_id' => $currentVersionIdInDb, // 最新的版本ID
                'current_version_number' => $currentDbVersion->version_number, // 最新的版本号
                'diff_base_vs_current' => $diffBaseVsCurrent, // 用户开始编辑的版本 vs 最新版本
                'diff_user_vs_current' => $diffUserVsCurrent, // 用户的提交 vs 最新版本
                'current_content' => $currentDbVersion->content, // 最新版本的内容原文
                'current_version_creator' => $currentDbVersion->creator->name ?? '未知用户', // 最新版本的作者
                'current_version_updated_at' => $currentDbVersion->created_at->toIso8601String(), // 最新版本的更新时间
            ], SymfonyResponse::HTTP_CONFLICT); // HTTP 状态码 409
        }

        // 检查是否有实际更改 (标题、内容、分类、标签)
        $currentVersionContent = $currentDbVersion?->content ?? ''; // 处理没有当前版本的情况
        $titleChanged = $validated['title'] !== $page->title;
        $contentChanged = $newContent !== $currentVersionContent;

        $currentCategoryIds = $page->categories->pluck('id')->sort()->values()->toArray();
        $newCategoryIds = collect($validated['category_ids'])->map(fn($id) => (int)$id)->sort()->values()->toArray();
        $categoriesChanged = $currentCategoryIds != $newCategoryIds;

        $currentTagIds = $page->tags->pluck('id')->sort()->values()->toArray();
        $newTagIds = collect($validated['tag_ids'] ?? [])->map(fn($id) => (int)$id)->sort()->values()->toArray();
        $tagsChanged = $currentTagIds != $newTagIds;

        $hasChanges = $titleChanged || $contentChanged || $categoriesChanged || $tagsChanged;

        // 如果没有任何实际更改（并且不是强制冲突也不是解决冲突），则不创建新版本
        if (!$hasChanges && !$forceConflict && $page->status !== WikiPage::STATUS_CONFLICT) {
            Log::info("No actual changes detected for page {$page->id} by user {$user->id}. Skipping new version creation.");
            // 清理草稿并注销编辑器
            WikiPageDraft::where('wiki_page_id', $page->id)->where('user_id', $user->id)->delete();
            $collaborationService->unregisterEditor($page, $user);
            return response()->json([
                'status' => 'no_changes',
                'message' => '未检测到更改，页面未更新。',
                'redirect_url' => route('wiki.show', $page->slug)
            ], SymfonyResponse::HTTP_OK);
        }


        // *** 执行保存逻辑 (正常保存、强制冲突、解决冲突) ***
        DB::beginTransaction();
        try {
            $logProperties = []; // 用于活动日志
            // 获取新的版本号 (在事务内锁定查询以防止竞争)
            $newVersionNumber = ($page->versions()->lockForUpdate()->latest('version_number')->value('version_number') ?? 0) + 1;
            $isNewVersionCurrent = true; // 默认新版本是当前版本
            $pageStatus = WikiPage::STATUS_PUBLISHED; // 默认页面状态为已发布
            $versionComment = $validated['comment'] ?: '更新页面';

            // 处理强制冲突的情况
            if ($forceConflict) {
                Log::warning("User {$user->id} is forcing conflict save for page {$page->id}. Base: {$submittedBaseVersionId}, DB Current: {$currentVersionIdInDb}");
                $pageStatus = WikiPage::STATUS_CONFLICT; // 页面状态设为冲突
                $isNewVersionCurrent = false; // 这个版本不是当前版本
                $versionComment = $validated['comment'] ?: "提交时版本冲突 (目标v{$currentDbVersion->version_number})";
                $logProperties['conflict_forced'] = true;
                $logProperties['db_version_at_conflict'] = $currentVersionIdInDb;
            }
            // 处理解决冲突的情况 (原本是冲突状态，现在提交新版本)
            else if ($page->status === WikiPage::STATUS_CONFLICT) {
                Log::info("User {$user->id} is resolving conflict for page {$page->id} by saving a new version.");
                $pageStatus = WikiPage::STATUS_PUBLISHED; // 状态恢复为已发布
                $versionComment = $validated['comment'] ?: '解决编辑冲突';
                $logProperties['conflict_resolved'] = true;
                // 标记之前的版本为非当前
                if ($currentVersionIdInDb) {
                    WikiVersion::where('id', $currentVersionIdInDb)->update(['is_current' => false]);
                }
            }

            // 记录标题是否有变化
            if ($titleChanged) $logProperties['title_changed'] = true;

            // 创建新版本记录
            $newVersion = WikiVersion::create([
                'wiki_page_id' => $page->id,
                'content' => $newContent,
                'created_by' => $user->id,
                'version_number' => $newVersionNumber,
                'comment' => $versionComment,
                'is_current' => $isNewVersionCurrent, // 标记是否为当前版本
            ]);
            $logProperties['version'] = $newVersionNumber;


            // 更新 WikiPage 表
            $updateData = [
                'status' => $pageStatus,
                'title' => $validated['title'],
            ];

            if ($isNewVersionCurrent) {
                // 如果这个新版本是当前版本，更新页面的 current_version_id
                $updateData['current_version_id'] = $newVersion->id;
                // 并将之前的当前版本标记为非当前 (如果在解决冲突时还没标记)
                if ($currentVersionIdInDb && $currentVersionIdInDb !== $newVersion->id && $pageStatus !== WikiPage::STATUS_CONFLICT) {
                    WikiVersion::where('id', $currentVersionIdInDb)->where('is_current', true)->update(['is_current' => false]);
                }
            }


            // 处理页面锁定状态
            if ($pageStatus === WikiPage::STATUS_CONFLICT) {
                $updateData['is_locked'] = true; // 冲突状态下锁定页面
                $updateData['locked_by'] = null;
                $updateData['locked_until'] = null;
            } else {
                $updateData['is_locked'] = false; // 解决冲突或正常更新后解锁
                $updateData['locked_by'] = null;
                $updateData['locked_until'] = null;
            }

            // 应用更新到 WikiPage
            $page->update($updateData);


            // 同步分类和标签
            if ($categoriesChanged) {
                $page->categories()->sync($validated['category_ids']);
                $logProperties['categories_changed'] = true;
            }
            if ($tagsChanged) {
                $page->tags()->sync($validated['tag_ids'] ?? []);
                $logProperties['tags_changed'] = true;
            }


            // 清理用户草稿
            WikiPageDraft::where('wiki_page_id', $page->id)->where('user_id', $user->id)->delete();

            // 注销编辑器
            $collaborationService->unregisterEditor($page, $user);


            // 记录活动日志
            $logAction = 'update';
            if ($forceConflict) {
                $logAction = 'force_conflict_save';
            }
            // elseif ($pageStatus === WikiPage::STATUS_PUBLISHED && $page->wasChanged('status')) { // 检查状态是否从冲突变为发布
            elseif ($pageStatus === WikiPage::STATUS_PUBLISHED && $page->getOriginal('status') === WikiPage::STATUS_CONFLICT) { // 检查状态是否从冲突变为发布
                $logAction = 'conflict_resolved';
            }
            $this->logActivity($logAction, $page, $logProperties);

            // 提交事务
            DB::commit();


            // 触发版本更新事件 (如果创建了新的 *当前* 版本)
            if ($isNewVersionCurrent) {
                Log::info("Page {$page->id} updated successfully to version {$newVersion->version_number} by user {$user->id}. New current version.");
                try {
                    event(new WikiPageVersionUpdated($page->id, $newVersion->id));
                    Log::info("Broadcasted WikiPageVersionUpdated event for page {$page->id}, new version ID: {$newVersion->id}");
                } catch (\Exception $e) {
                    Log::error("Failed to broadcast WikiPageVersionUpdated event for page {$page->id}: " . $e->getMessage());
                }
                // 返回成功响应
                return response()->json([
                    'status' => 'success',
                    'message' => '页面更新成功！',
                    'new_version_id' => $newVersion->id,
                    'new_version_number' => $newVersion->version_number,
                    'redirect_url' => route('wiki.show', $page->slug) // 告诉前端跳转到哪里
                ]);
            }
            // 如果是强制冲突保存
            else {
                Log::warning("Page {$page->id} conflict forced by user {$user->id}. New non-current version {$newVersion->version_number} created.");
                // 返回强制冲突成功的响应
                return response()->json([
                    'status' => 'conflict_forced',
                    'message' => '您的更改已保存，但与当前版本存在冲突。页面已被锁定，请等待处理。',
                    'new_version_id' => $newVersion->id, // 新创建的冲突版本的ID
                    'new_version_number' => $newVersion->version_number,
                    'redirect_url' => route('wiki.show', $page->slug) // 通常还是跳转回展示页看冲突状态
                ]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error updating page {$page->id} by user {$user->id}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            // 返回通用错误
            return response()->json([
                'status' => 'error',
                'message' => '保存页面时发生内部错误，请稍后重试。'
            ], SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(WikiPage $page): RedirectResponse
    {
        $this->authorize('delete', $page); // 检查权限

        try {
            $pageTitle = $page->title;
            $pageId = $page->id;
            $userId = Auth::id();

            // 软删除
            if ($page->delete()) {
                // 记录日志
                $this->logActivity('delete', $page, ['title' => $pageTitle, 'soft_deleted' => true]);
                Log::info("Wiki page {$pageId} ('{$pageTitle}') soft deleted by user {$userId}.");
                return redirect()->route('wiki.index')
                    ->with('flash', ['message' => ['type' => 'success', 'text' => '页面已移至回收站！']]);
            } else {
                Log::error("Failed to soft delete page {$pageId} ('{$pageTitle}') by user {$userId}.");
                return back()->withErrors(['general' => '将页面移至回收站时出错，请稍后重试。']);
            }
        } catch (\Exception $e) {
            Log::error("Error soft deleting page {$page->id}: " . $e->getMessage());
            return back()->withErrors(['general' => '删除页面时发生内部错误，请稍后重试。']);
        }
    }

    public function trashIndex(Request $request): InertiaResponse
    {
        $this->authorize('viewTrash', WikiPage::class);

        $query = WikiPage::onlyTrashed()
            ->with(['creator:id,name', 'categories:id,name,slug', 'tags:id,name,slug']);

        // 如果有搜索请求
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $trashedPages = $query->latest('deleted_at') // 按删除时间排序
            ->paginate(15)
            ->withQueryString(); // 保持查询参数

        return Inertia::render('Wiki/Trash/Index', [
            'trashedPages' => $trashedPages,
            'filters' => $request->only(['search']), // 将搜索词传回视图
            'flash' => session('flash'),
        ]);
    }

    public function restore(int $pageId): RedirectResponse
    {
        $this->authorize('restore', WikiPage::class); // 先检查通用权限

        try {
            // 找到仅在回收站中的页面
            $page = WikiPage::onlyTrashed()->findOrFail($pageId);
            $this->authorize('restore', $page); // 再检查具体页面权限（如果需要的话，Policy 里可以不用$page）

            // 执行恢复操作
            $page->restore();
            // 记录日志
            $this->logActivity('restore', $page);
            Log::info("Wiki page {$page->id} ('{$page->title}') restored from trash by user " . Auth::id());

            return redirect()->route('wiki.trash.index')
                ->with('flash', ['message' => ['type' => 'success', 'text' => '页面已成功恢复！']]);
        } catch (ModelNotFoundException $e) {
            Log::warning("Attempted to restore non-existent or not-trashed page ID: {$pageId}");
            return redirect()->route('wiki.trash.index')
                ->withErrors(['general' => '无法找到要恢复的页面。']);
        } catch (\Exception $e) {
            Log::error("Error restoring page {$pageId} from trash: " . $e->getMessage());
            return redirect()->route('wiki.trash.index')
                ->withErrors(['general' => '恢复页面时出错，请稍后重试。']);
        }
    }

    public function forceDelete(int $pageId): RedirectResponse
    {
        $this->authorize('forceDelete', WikiPage::class); // 先检查通用权限

        try {
            // 找到仅在回收站中的页面
            $page = WikiPage::onlyTrashed()->findOrFail($pageId);
            $this->authorize('forceDelete', $page); // 再检查具体页面权限

            $pageTitle = $page->title;

            // --- 强制删除需要在事务中处理 ---
            DB::beginTransaction();
            try {
                $page->forceDelete(); // 强制删除
                // 记录日志
                // 注意：由于模型已被删除，直接传递 $page 可能无法获取所有信息，传递预存信息更稳妥
                $this->logActivity('force_delete', $page, ['title' => $pageTitle]); // 传递 $page 可能仍有效，取决于事件监听
                Log::info("Wiki page {$pageId} ('{$pageTitle}') permanently deleted by user " . Auth::id());
                DB::commit();
                return redirect()->route('wiki.trash.index')
                    ->with('flash', ['message' => ['type' => 'success', 'text' => '页面已永久删除！']]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e; // 将异常抛出给上层处理
            }
        } catch (ModelNotFoundException $e) {
            Log::warning("Attempted to force delete non-existent or not-trashed page ID: {$pageId}");
            return redirect()->route('wiki.trash.index')
                ->withErrors(['general' => '无法找到要永久删除的页面。']);
        } catch (\Exception $e) {
            Log::error("Error force deleting page {$pageId}: " . $e->getMessage());
            return redirect()->route('wiki.trash.index')
                ->withErrors(['general' => '永久删除页面时出错，请稍后重试。']);
        }
    }

    public function showVersion(WikiPage $page, int $versionNumber): InertiaResponse
    {
        $this->authorize('viewHistory', $page); // 需要查看历史的权限

        // 查找特定版本的记录
        $versionRecord = WikiVersion::where('wiki_page_id', $page->id)
            ->where('version_number', $versionNumber)
            ->with('creator:id,name') // 预加载创建者信息
            ->firstOrFail(); // 如果找不到就抛出404

        // 加载页面的基本信息（创建者，分类，标签）用于展示
        $page->load(['creator:id,name', 'categories:id,name,slug', 'tags:id,name,slug']);

        return Inertia::render('Wiki/ShowVersion', [
            'page' => $page->only('id', 'title', 'slug', 'creator', 'categories', 'tags'), // 只传递需要的页面信息
            'version' => $versionRecord->only('id', 'version_number', 'content', 'comment', 'created_at', 'creator'), // 只传递需要的版本信息
        ]);
    }

    public function history(WikiPage $page): InertiaResponse
    {
        $this->authorize('viewHistory', $page);

        $versions = WikiVersion::where('wiki_page_id', $page->id)
            ->with('creator:id,name') // 预加载创建者信息
            ->orderBy('version_number', 'desc') // 按版本号降序排列
            ->select('id', 'version_number', 'comment', 'created_at', 'created_by', 'is_current') // 选择需要的字段
            ->paginate(15); // 分页

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

        // 使用 DiffService 生成 HTML 差异
        $diffService = app(DiffService::class);
        $diffHtml = $diffService->generateDiffHtml($fromVersion->content, $toVersion->content);

        // 确定哪个是旧版本，哪个是新版本（以便于视图展示）
        $olderVersion = $fromVersion->version_number < $toVersion->version_number ? $fromVersion : $toVersion;
        $newerVersion = $fromVersion->version_number > $toVersion->version_number ? $fromVersion : $toVersion;

        return Inertia::render('Wiki/Compare', [
            'page' => $page->only('id', 'title', 'slug'),
            'fromVersion' => $olderVersion->only('id', 'version_number', 'content', 'comment', 'created_at', 'creator'),
            'toVersion' => $newerVersion->only('id', 'version_number', 'content', 'comment', 'created_at', 'creator'),
            'fromCreator' => $olderVersion->creator, // 方便前端直接访问
            'toCreator' => $newerVersion->creator, // 方便前端直接访问
            'diffHtml' => $diffHtml,
        ]);
    }

    public function revertToVersion(Request $request, WikiPage $page, int $versionNumberToRevertTo): RedirectResponse
    {
        $this->authorize('revert', $page); // 检查权限
        $user = Auth::user();

        // 找到要恢复到的那个版本
        $versionToRevert = WikiVersion::where('wiki_page_id', $page->id)
            ->where('version_number', $versionNumberToRevertTo)
            ->firstOrFail();

        // 如果页面处于冲突状态，不允许恢复
        if ($page->status === WikiPage::STATUS_CONFLICT) {
            return back()->withErrors(['general' => '无法恢复版本，页面当前处于冲突状态，请先解决冲突。']);
        }

        DB::beginTransaction();
        try {
            $currentVersionIdInDb = $page->current_version_id;
            // 获取最新的版本号，准备创建新版本
            $latestVersionNumber = $page->versions()->latest('version_number')->value('version_number') ?? 0;

            // 创建一个新版本，内容来自要恢复的版本
            $newVersion = WikiVersion::create([
                'wiki_page_id' => $page->id,
                'content' => $versionToRevert->content,
                'created_by' => $user->id,
                'version_number' => $latestVersionNumber + 1,
                'comment' => '恢复自版本 ' . $versionNumberToRevertTo,
                'is_current' => true, // 这个新版本将成为当前版本
            ]);

            // 如果之前有当前版本，将其标记为非当前
            if ($currentVersionIdInDb) {
                WikiVersion::where('id', $currentVersionIdInDb)->update(['is_current' => false]);
            }

            // 更新页面，指向这个新的当前版本
            $page->update(['current_version_id' => $newVersion->id]);

            // 记录日志
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


    public function showConflicts(WikiPage $page, DiffService $diffService): InertiaResponse|RedirectResponse
    {
        $this->authorize('resolveConflict', $page);

        // 确保页面确实处于冲突状态
        if ($page->status !== WikiPage::STATUS_CONFLICT) {
            Log::info("Attempted to show conflicts for page {$page->id} which is not in conflict.");
            return redirect()->route('wiki.show', $page->slug)
                ->with('flash', ['message' => ['type' => 'info', 'text' => '该页面当前没有冲突需要解决。']]);
        }

        // 获取“当前”版本（在冲突场景下，这通常是最新的那个导致冲突的版本）
        $currentVersion = $page->currentVersion()->with('creator:id,name')->first();

        // 获取另一个“冲突”的版本（通常是强制提交的那个，或时间上稍早的那个未被设为current的最新版本）
        // 逻辑是：找到所有非当前的版本，按版本号降序排，取第一个
        $conflictingVersion = $page->versions()
            ->where('is_current', false) // 关键：非当前版本
            ->orderBy('version_number', 'desc')
            ->with('creator:id,name')
            ->first();

        // 如果找不到必要的版本信息
        if (!$currentVersion || !$conflictingVersion) {
            Log::error("Conflict resolution error for page {$page->id}: Missing current or conflicting version data.");
            return redirect()->route('wiki.show', $page->slug)
                ->withErrors(['general' => '无法加载冲突版本信息，请联系管理员。']);
        }

        // 生成两个冲突版本之间的差异
        // 注意比较顺序，通常是 旧 vs 新
        $diffHtml = $diffService->generateDiffHtml($conflictingVersion->content, $currentVersion->content);


        return Inertia::render('Wiki/ShowConflicts', [
            'page' => $page->only('id', 'title', 'slug'),
            'conflictVersions' => [
                // 将两个版本都传递给前端
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
            $cleanContent = Purifier::clean($validated['content']); // 清理提交的内容
            $latestVersionNumber = $page->versions()->where('wiki_page_id', $page->id)->latest('version_number')->value('version_number') ?? 0;

            // 1. 创建一个新的版本，内容是用户解决后的内容
            $newVersion = WikiVersion::create([
                'wiki_page_id' => $page->id,
                'content' => $cleanContent,
                'created_by' => $user->id,
                'version_number' => $latestVersionNumber + 1,
                'comment' => $validated['resolution_comment'] ?: '解决编辑冲突',
                'is_current' => true, // 解决后的版本成为新的当前版本
            ]);

            // 2. 将页面所有其他版本标记为非当前 (确保只有新版本是 current)
            WikiVersion::where('wiki_page_id', $page->id)
                ->where('id', '!=', $newVersion->id)
                ->update(['is_current' => false]);

            // 3. 更新页面的当前版本ID，并将状态改回 published，并解锁
            $page->update([
                'current_version_id' => $newVersion->id,
                'status' => WikiPage::STATUS_PUBLISHED,
                'is_locked' => false,
                'locked_by' => null,
                'locked_until' => null,
            ]);


            // 记录日志
            if (method_exists($page, 'logCustomActivity')) { // 如果模型使用了特定的日志方法
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
            Log::error("Error resolving conflict for page {$page->id} by user {$user->id}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return back()->withErrors(['general' => '解决冲突时发生内部错误，请稍后重试。'])->withInput();
        }
    }

    // 协同编辑相关 API (如果前端调用的话)
    public function getEditors(WikiPage $page, CollaborationService $collaborationService): JsonResponse
    {
        $editors = $collaborationService->getEditors($page->id);
        return response()->json(['editors' => array_values($editors)]);
    }

    public function registerEditor(Request $request, WikiPage $page, CollaborationService $collaborationService): JsonResponse
    {
        $user = Auth::user();
        $success = $collaborationService->registerEditor($page, $user);
        if (!$success) {
            // 根据 registerEditor 的返回状态判断是否因为达到上限而拒绝
            return response()->json(['success' => false, 'message' => '已达到编辑人数上限或页面暂时锁定'], 429); // 429 Too Many Requests
        }
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
            Log::error("Error sending discussion message for page {$page->id} by user {$user->id}: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => '发送消息失败'], SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // 辅助方法：记录活动日志
    protected function logActivity(string $action, Model $subject, ?array $properties = null): void
    {
        if (app()->runningInConsole() && !app()->runningUnitTests()) {
            return;
        }
        try {
            ActivityLog::log($action, $subject, $properties);
        } catch (\Exception $e) {
            Log::error("Failed to log activity: Action={$action}, Subject={$subject->getTable()}:{$subject->getKey()}, Error: " . $e->getMessage());
        }
    }

    public function preview(Request $request): InertiaResponse
    {
        $user = Auth::user(); // 获取当前用户，可能为 null

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:wiki_categories,id', // 验证 ID 存在
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'integer|exists:wiki_tags,id', // 验证 ID 存在
        ]);

        // 清理 HTML 内容
        $cleanContent = Purifier::clean($validated['content']);

        // 获取选中的分类和标签名称用于显示
        $categories = WikiCategory::whereIn('id', $validated['category_ids'] ?? [])->select('id', 'name', 'slug')->get();
        $tags = WikiTag::whereIn('id', $validated['tag_ids'] ?? [])->select('id', 'name', 'slug')->get();

        // 创建一个模拟的页面对象用于传递给 Show.vue 页面
        // 注意：这里的属性需要尽可能模仿 WikiPage 模型返回的结构
        $pseudoPage = new \stdClass;
        $pseudoPage->id = 0; // 预览时 ID 为 0 或 null
        $pseudoPage->title = $validated['title'];
        $pseudoPage->slug = 'preview'; // 固定的预览 slug
        $pseudoPage->creator = $user ? $user->only('id', 'name') : ['id' => 0, 'name' => '预览用户']; // 模拟创建者
        $pseudoPage->created_at = now();
        $pseudoPage->updated_at = now();
        $pseudoPage->categories = $categories->map(fn($cat) => $cat->toArray())->toArray(); // 格式化分类
        $pseudoPage->tags = $tags->map(fn($tag) => $tag->toArray())->toArray();       // 格式化标签
        $pseudoPage->status = 'preview'; // 添加预览状态
        $pseudoPage->currentVersion = null; // 在下面创建模拟版本

        // 创建一个模拟的版本对象
        $pseudoVersion = new \stdClass;
        $pseudoVersion->id = 0; // 预览时版本 ID 为 0 或 null
        $pseudoVersion->content = $cleanContent;
        $pseudoVersion->creator = $pseudoPage->creator; // 版本创建者同页面创建者
        $pseudoVersion->created_at = now();
        $pseudoVersion->version_number = '预览'; // 版本号
        $pseudoVersion->comment = '实时预览内容';
        $pseudoVersion->is_current = true;

        // 传递给 Show.vue 的数据结构要匹配它期望的 props
        return Inertia::render('Wiki/Show', [
            'page' => (array) $pseudoPage,       // 将对象转为数组
            'currentVersion' => (array) $pseudoVersion, // 将对象转为数组
            'isLocked' => false,               // 预览时总是未锁定
            'lockedBy' => null,
            'draft' => null,                 // 预览时无草稿
            'canEditPage' => false,              // 预览页面不可编辑
            'canResolveConflict' => false,     // 预览页面无冲突
            'error' => null,
            'comments' => [],                   // 预览时无评论
            'flash' => null,                  // 预览时无闪存消息
            'isPreview' => true,              // 标记为预览模式
        ]);
    }

    // 删除用户自身草稿的 API
    public function deleteMyDraft(Request $request, WikiPage $page): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => '未认证'], 401);
        }
        try {
            $deletedCount = WikiPageDraft::where('wiki_page_id', $page->id)
                ->where('user_id', $user->id)
                ->delete();
            if ($deletedCount > 0) {
                Log::info("User {$user->id} deleted their draft for page {$page->id}.");
                app(CollaborationService::class)->unregisterEditor($page, $user); // 用户删除草稿后也注销编辑状态
                return response()->json(['message' => '草稿已成功删除'], 200);
            } else {
                Log::info("User {$user->id} attempted to delete draft for page {$page->id}, but no draft found.");
                app(CollaborationService::class)->unregisterEditor($page, $user); // 即便没草稿，也尝试注销，以防万一状态不同步
                return response()->json(['message' => '未找到您的草稿'], 200); // 即使没找到也返回成功，避免前端错误
            }
        } catch (\Exception $e) {
            Log::error("Error deleting draft for page {$page->id} by user {$user->id}: " . $e->getMessage());
            return response()->json(['message' => '删除草稿时出错'], 500);
        }
    }
}

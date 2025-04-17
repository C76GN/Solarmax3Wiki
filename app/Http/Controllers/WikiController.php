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
        // 原有查询构建逻辑...
        $query = WikiPage::with(['creator:id,name', 'categories:id,name,slug', 'tags:id,name,slug'])
            // ->where('status', WikiPage::STATUS_PUBLISHED); // <-- 注释掉或修改这里
            ->whereIn('status', [WikiPage::STATUS_PUBLISHED, WikiPage::STATUS_CONFLICT]); // <-- 修改为包含 conflict

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

        // 状态筛选
        if ($request->filled('status') && in_array($request->status, [WikiPage::STATUS_PUBLISHED, WikiPage::STATUS_CONFLICT])) {
            $query->where('status', $request->status); // 新增状态筛选
        }

        // 搜索筛选
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // 添加排序，让冲突页面可能靠前或有特定排序
        $query->orderByRaw("FIELD(status, '".WikiPage::STATUS_CONFLICT."', '".WikiPage::STATUS_PUBLISHED."') DESC") // 让 conflict 优先
            ->latest('updated_at');

        $pages = $query
            ->select('id', 'title', 'slug', 'created_by', 'status', 'created_at', 'updated_at', 'current_version_id') // 确保查询 status
            ->paginate(15)
            ->withQueryString();

        // 加载 categories 和 tags 保持不变...
        $categories = WikiCategory::withCount(['pages' => function ($query) {
            $query->whereIn('status', [WikiPage::STATUS_PUBLISHED, WikiPage::STATUS_CONFLICT]); // 同样更新统计
        }])
            ->orderBy('order')
            ->select('id', 'name', 'slug')
            ->get();

        $tags = WikiTag::withCount(['pages' => function ($query) {
            $query->whereIn('status', [WikiPage::STATUS_PUBLISHED, WikiPage::STATUS_CONFLICT]); // 同样更新统计
        }])
            ->select('id', 'name', 'slug')
            ->get();

        return Inertia::render('Wiki/Index', [
            'pages' => $pages,
            'categories' => $categories,
            'tags' => $tags,
            // 'filters' => $request->only(['category', 'tag', 'search']), // 包含 status
            'filters' => $request->only(['category', 'tag', 'search', 'status']), // 传递 status 筛选器
            'flash' => session('flash'),
        ]);
    }

    public function show(string $slug): InertiaResponse
    {
        $page = WikiPage::where('slug', $slug)
            ->with(['creator:id,name', 'categories:id,name,slug', 'tags:id,name,slug'])
            // Eager load a minimal version of currentVersion even if conflicted
            ->with(['currentVersion' => function ($query) {
                $query->select('id', 'wiki_page_id', 'content', 'created_at', 'created_by', 'version_number', 'comment')
                    ->with('creator:id,name');
            }])
            // Eager load locker only if locked
            ->with(['locker:id,name'])
            ->firstOrFail();

        // 获取最新版本，即使它不是 current_version_id (冲突场景下 current_version_id 可能指向旧的稳定版)
        $latestVersion = $page->versions()->orderBy('version_number', 'desc')->with('creator:id,name')->first();

        // 判断是否需要跳转到冲突特定页面 (如果用户*无权解决*) - 现在倾向于在 Show.vue 里提示
        // if ($page->status === WikiPage::STATUS_CONFLICT && Gate::denies('resolveConflict', $page)) {
        //    // 理论上，如果只是普通用户，仍然可以查看最后一次“发布”的版本
        //    // 或者根据你的策略，完全不允许访问 Show 页面，但这里选择显示带警告的 Show 页面
        //     Log::warning('Normal user accessing conflicted page. Showing with warning.', ['page_id' => $page->id, 'user_id' => Auth::id()]);
        // }

        // 检查数据一致性错误（保留）
        if ($page->current_version_id && ! $page->currentVersion) {
            Log::error("Data Consistency Error: WikiPage ID {$page->id} ('{$page->title}') has current_version_id={$page->current_version_id}, but WikiVersion not found.");
            // Consider what to show here. Maybe just the page data without content.
            // Let's proceed but signal an error to the frontend.
        }

        // 总是加载评论
        $page->load(['comments' => function ($query) {
            $query->where('is_hidden', false)
                ->whereNull('parent_id')
                ->with(['user:id,name', 'replies' => function ($q) {
                    $q->where('is_hidden', false)->with('user:id,name')->latest('created_at');
                }])
                ->latest('created_at');
        }]);

        $isLocked = $page->isLocked();
        $lockedBy = $isLocked ? $page->locker : null;

        $draft = null;
        if (Auth::check()) {
            $draft = WikiPageDraft::where('wiki_page_id', $page->id)
                ->where('user_id', Auth::id())
                ->select('content', 'last_saved_at')
                ->first();
        }

        // 决定可编辑性 (编辑按钮的显示与否)
        $canEdit = Auth::check()
            && $page->status !== WikiPage::STATUS_CONFLICT // 不能直接编辑冲突状态
            && Gate::allows('update', $page); // 使用 Policy 判断权限和锁状态

        // 解决冲突的权限是独立的
        $canResolveConflict = Auth::check() && Gate::allows('resolveConflict', $page);

        return Inertia::render('Wiki/Show', [
            'page' => $page->toArray(), // 将主要页面数据转为数组
            // 传递用于显示的版本信息 (优先显示最新版，如果是冲突状态可能不是current_version_id)
            'currentVersion' => $latestVersion ? $latestVersion->toArray() : ($page->currentVersion ? $page->currentVersion->toArray() : null),
            'isLocked' => $isLocked,
            'lockedBy' => $lockedBy ? $lockedBy->only('id', 'name') : null,
            'draft' => $draft,
            'canEditPage' => $canEdit,
            'canResolveConflict' => $canResolveConflict, // 明确传递解决权限
            'isConflictPage' => $page->status === WikiPage::STATUS_CONFLICT, // 传递页面是否冲突
            'error' => session('error') ?: ($page->current_version_id && ! $page->currentVersion ? '无法加载指定的内容版本，数据可能存在问题。' : null), // 更明确的错误信息
            'comments' => $page->comments->toArray(), // 确保评论也转为数组
            'flash' => session('flash'),
            'isPreview' => false,
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
            $slug = $originalSlug.'-'.$counter++;
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
            if (! empty($validated['tag_ids'])) {
                $page->tags()->attach($validated['tag_ids']);
            }

            // 5. 记录活动日志
            $this->logActivity('create', $page, ['version' => $version->version_number]);

            DB::commit(); // 提交事务

            Log::info("Wiki page {$page->id} ('{$page->title}') created successfully by user ".Auth::id());

            return redirect()->route('wiki.show', $page->slug)
                ->with('flash', ['message' => ['type' => 'success', 'text' => '页面创建成功！']]);
        } catch (\Exception $e) {
            DB::rollBack(); // 回滚事务
            Log::error('Error creating wiki page: '.$e->getMessage());

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
        if ($editorCount >= 2 && ! $isCurrentUserEditing) {
            Log::warning("Editor limit reached for page {$page->id}. User {$user->id} denied entry.");

            return redirect()->route('wiki.show', $page->slug)
                ->with('flash', ['message' => ['type' => 'error', 'text' => '此页面当前已有两人正在编辑，请稍后再试。']]);
        }
        // 检查冲突状态和权限
        $isInConflict = $page->status === WikiPage::STATUS_CONFLICT;
        $canResolveConflict = $user && $user->can('wiki.resolve_conflict');
        if ($isInConflict && ! $canResolveConflict) {
            Log::warning("User {$user->id} attempted to edit conflicted page {$page->id} without resolve permissions.");

            return redirect()->route('wiki.show', $page->slug)
                ->with('flash', ['message' => ['type' => 'error', 'text' => '此页面处于冲突状态，需要权限解决后才能编辑。']]);
        }
        // 使用 Gate::allows 检查是否允许编辑 (它内部会调用 Policy 的 update 方法)
        $isEditable = Gate::allows('update', $page);
        // 如果不可编辑且不是冲突待解决状态（即被他人锁定），则重定向
        if (! $isEditable && ! $isInConflict) {
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
            'hasDraft' => ! is_null($draft),
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
            $page = WikiPage::findOrFail((int) $pageId); // 确保页面存在
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

    // 更新 Wiki 页面
    public function update(Request $request, WikiPage $page, CollaborationService $collaborationService, DiffService $diffService): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $page);
        $user = Auth::user();

        $submittedBaseVersionIdInput = $request->input('version_id');
        Log::info("WikiController@update: Page ID {$page->id}, User ID {$user->id}. Received base version ID input: {$submittedBaseVersionIdInput}.");

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'required|exists:wiki_categories,id',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:wiki_tags,id',
            'comment' => 'nullable|string|max:255',
            'version_id' => [ // User's base version ID they started editing from
                'nullable', // Can be null for the very first edit or if somehow lost
                'integer',
                function ($attribute, $value, $fail) use ($page) {
                    if ($value !== null && $value !== 0 && ! WikiVersion::where('wiki_page_id', $page->id)->where('id', $value)->exists()) {
                        $fail('提交所基于的版本 ID 无效或不属于此页面。');
                    }
                },
            ],
            'force_conflict' => 'sometimes|boolean', // User explicitly chose "Force Save" in modal
        ]);

        // 从数据库重新加载页面以获取最新状态
        $page->refresh();
        $page->load(['currentVersion.creator:id,name', 'categories:id', 'tags:id']); // 预加载关联数据

        $newContent = Purifier::clean($validated['content']);
        $submittedBaseVersionId = ($validated['version_id'] === null || $validated['version_id'] === 0) ? null : (int) $validated['version_id']; // 处理 null 或 0 情况
        $forceConflict = $request->boolean('force_conflict', false);
        $isPageOriginallyInConflict = $page->status === WikiPage::STATUS_CONFLICT; // 保存时的原始冲突状态

        $currentDbVersion = $page->currentVersion;
        $currentVersionIdInDb = $currentDbVersion?->id;

        Log::info("WikiController@update: Page ID {$page->id}. DB current version ID: {$currentVersionIdInDb}. Submitted base ID: {$submittedBaseVersionId}. Force conflict flag: ".($forceConflict ? 'true' : 'false'));

        // **版本冲突检测 (Stale Check)**
        // 只有在不是强制提交，且提交的基础版本ID 和 数据库当前版本ID 不一致时，才触发冲突检测流程
        if (! $forceConflict && $submittedBaseVersionId !== $currentVersionIdInDb && $submittedBaseVersionId !== null) {
            Log::warning("Stale version detected for page {$page->id}. User base: {$submittedBaseVersionId}, DB current: {$currentVersionIdInDb}. User: {$user->id}");

            // 尝试加载基础版本和当前版本用于比较
            $baseVersionForDiff = WikiVersion::find($submittedBaseVersionId);

            if (! $baseVersionForDiff || ! $currentDbVersion) {
                Log::error("Cannot generate diffs for stale check: Base version {$submittedBaseVersionId} or Current DB version {$currentVersionIdInDb} not found for page {$page->id}.");

                return response()->json([
                    'status' => 'error',
                    'message' => '无法加载用于比较的版本信息，数据可能已发生变化，请刷新页面后重试。',
                    'current_version_id' => $currentVersionIdInDb, // Return latest known ID
                ], SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR);
            }

            // 生成差异 HTML
            $diffBaseVsCurrent = $diffService->generateDiffHtml($baseVersionForDiff->content, $currentDbVersion->content);
            $diffUserVsCurrent = $diffService->generateDiffHtml($newContent, $currentDbVersion->content); // 使用用户提交的新内容

            // 返回 409 Conflict 响应，包含差异信息
            return response()->json([
                'status' => 'stale_version',
                'message' => '页面已被其他用户更新。请处理版本差异。',
                'current_version_id' => $currentVersionIdInDb,
                'current_version_number' => $currentDbVersion->version_number,
                'diff_base_vs_current' => $diffBaseVsCurrent, // Your Version vs. Latest
                'diff_user_vs_current' => $diffUserVsCurrent, // User's Edit vs. Latest
                'current_content' => $currentDbVersion->content, // Provide latest content for "Discard & Edit New"
                'current_version_creator' => $currentDbVersion->creator->name ?? '未知用户',
                'current_version_updated_at' => $currentDbVersion->created_at->toIso8601String(),
            ], SymfonyResponse::HTTP_CONFLICT); // 409 Conflict Status
        }

        // --- 版本创建/更新逻辑 ---

        $currentVersionContent = $currentDbVersion?->content ?? '';
        $titleChanged = $validated['title'] !== $page->title;
        $contentChanged = $newContent !== $currentVersionContent;
        $currentCategoryIds = $page->categories->pluck('id')->sort()->values()->toArray();
        $newCategoryIds = collect($validated['category_ids'])->map(fn ($id) => (int) $id)->sort()->values()->toArray();
        $categoriesChanged = $currentCategoryIds != $newCategoryIds;
        $currentTagIds = $page->tags->pluck('id')->sort()->values()->toArray();
        $newTagIds = collect($validated['tag_ids'] ?? [])->map(fn ($id) => (int) $id)->sort()->values()->toArray();
        $tagsChanged = $currentTagIds != $newTagIds;

        $hasChanges = $titleChanged || $contentChanged || $categoriesChanged || $tagsChanged;

        // 如果没有实际变化，且不是强制冲突，并且页面原本不是冲突状态，则不创建新版本
        if (! $hasChanges && ! $forceConflict && ! $isPageOriginallyInConflict) {
            Log::info("No actual changes detected for page {$page->id} by user {$user->id}. Skipping new version creation.");
            WikiPageDraft::where('wiki_page_id', $page->id)->where('user_id', $user->id)->delete();
            $collaborationService->unregisterEditor($page, $user);

            return response()->json([
                'status' => 'no_changes',
                'message' => '未检测到更改，页面未更新。',
                'redirect_url' => route('wiki.show', $page->slug),
            ], SymfonyResponse::HTTP_OK);
        }

        // --- 执行数据库事务 ---
        DB::beginTransaction();
        try {
            $logProperties = [];
            $newVersionNumber = ($page->versions()->lockForUpdate()->latest('version_number')->value('version_number') ?? 0) + 1;
            $isNewVersionCurrent = true; // 默认新版本是当前版本
            $pageStatus = WikiPage::STATUS_PUBLISHED; // 默认页面状态是已发布
            $versionComment = $validated['comment'] ?: '更新页面'; // 默认评论

            // Case 1: 用户在冲突模态框中选择了 "强制保存（我的版本）"
            if ($forceConflict) {
                Log::warning("User {$user->id} is forcing conflict save for page {$page->id}. Base: {$submittedBaseVersionId}, DB Current: {$currentVersionIdInDb}");
                $pageStatus = WikiPage::STATUS_CONFLICT; // 页面状态设为冲突
                $isNewVersionCurrent = false; // 这个强制保存的版本不是当前生效版本
                $versionComment = $validated['comment'] ?: "强制提交版本（目标是已过时的 v{$currentDbVersion?->version_number}）";
                $logProperties['conflict_forced'] = true;
                $logProperties['db_version_at_conflict'] = $currentVersionIdInDb;
            }
            // Case 2: 页面原本处于冲突状态，现在提交被认为是解决冲突的操作
            elseif ($isPageOriginallyInConflict) {
                Log::info("User {$user->id} is resolving conflict for page {$page->id} by saving a new version.");
                $pageStatus = WikiPage::STATUS_PUBLISHED; // 冲突解决后页面恢复为已发布
                $versionComment = $validated['comment'] ?: '解决编辑冲突';
                $logProperties['conflict_resolved'] = true;
                // 在创建新版本前，将旧的 current_version_id 标记为非当前 (如果存在)
                if ($currentVersionIdInDb) {
                    WikiVersion::where('id', $currentVersionIdInDb)->update(['is_current' => false]);
                }
            }

            // 记录标题变更
            if ($titleChanged) {
                $logProperties['title_changed'] = true;
            }

            // 创建新版本记录
            $newVersion = WikiVersion::create([
                'wiki_page_id' => $page->id,
                'content' => $newContent,
                'created_by' => $user->id,
                'version_number' => $newVersionNumber,
                'comment' => $versionComment,
                'is_current' => $isNewVersionCurrent, // 根据是否强制冲突设置
            ]);
            $logProperties['version'] = $newVersionNumber;

            // 准备更新页面的数据
            $updateData = [
                'status' => $pageStatus, // 设置新的页面状态
                'title' => $validated['title'],
            ];

            // 只有在新版本应该成为当前版本时，才更新页面的 current_version_id
            if ($isNewVersionCurrent) {
                $updateData['current_version_id'] = $newVersion->id;
                // 如果旧的当前版本 ID 存在且不等于新版本 ID，且不是强制冲突保存，则标记旧版本为非当前
                if ($currentVersionIdInDb && $currentVersionIdInDb !== $newVersion->id && ! $forceConflict) {
                    // Ensure the update only affects the one intended record and check if it was actually marked as current.
                    $affected = WikiVersion::where('id', $currentVersionIdInDb)->where('is_current', true)->update(['is_current' => false]);
                    if ($affected === 0) {
                        Log::warning("WikiController@update: Attempted to mark old version {$currentVersionIdInDb} as not current, but it wasn't marked as current or didn't exist.");
                    }
                }
            }

            // 更新页面的锁定状态
            if ($pageStatus === WikiPage::STATUS_CONFLICT) {
                $updateData['is_locked'] = true; // 冲突页面保持锁定
                $updateData['locked_by'] = null; // 清除具体锁定人信息
                $updateData['locked_until'] = null; // 清除锁定时间
            } elseif ($isPageOriginallyInConflict && $pageStatus === WikiPage::STATUS_PUBLISHED) {
                // If conflict is being resolved, unlock
                $updateData['is_locked'] = false;
                $updateData['locked_by'] = null;
                $updateData['locked_until'] = null;
            } else {
                // For normal updates, ensure unlocked state
                $updateData['is_locked'] = false;
                $updateData['locked_by'] = null;
                $updateData['locked_until'] = null;
            }

            $page->update($updateData);

            // 同步分类和标签
            if ($categoriesChanged) {
                $page->categories()->sync($validated['category_ids']);
                $logProperties['categories_changed'] = true;
            }
            if ($tagsChanged || $request->has('tag_ids')) { // Check if tag_ids exists in request even if no changes
                $page->tags()->sync($validated['tag_ids'] ?? []);
                $logProperties['tags_changed'] = true;
            }

            // 清理草稿和编辑者列表
            WikiPageDraft::where('wiki_page_id', $page->id)->where('user_id', $user->id)->delete();
            $collaborationService->unregisterEditor($page, $user);

            // 确定日志操作类型
            $logAction = 'update'; // 默认是更新
            if ($forceConflict) {
                $logAction = 'force_conflict_save';
            } elseif ($isPageOriginallyInConflict && $pageStatus === WikiPage::STATUS_PUBLISHED) {
                $logAction = 'conflict_resolved';
            }
            $this->logActivity($logAction, $page, $logProperties);

            DB::commit(); // 提交事务

            // 广播事件（仅当新版本是当前版本时）
            if ($isNewVersionCurrent) {
                Log::info("Page {$page->id} updated successfully to version {$newVersion->version_number} by user {$user->id}. New current version ID: {$newVersion->id}. Status: {$pageStatus}");
                try {
                    event(new WikiPageVersionUpdated($page->id, $newVersion->id));
                    Log::info("Broadcasted WikiPageVersionUpdated event for page {$page->id}, new version ID: {$newVersion->id}");
                } catch (\Exception $e) {
                    Log::error("Failed to broadcast WikiPageVersionUpdated event for page {$page->id}: ".$e->getMessage());
                }

                // 返回成功响应
                return response()->json([
                    'status' => $isPageOriginallyInConflict ? 'conflict_resolved' : 'success',
                    'message' => $isPageOriginallyInConflict ? '冲突已成功解决！' : '页面更新成功！',
                    'new_version_id' => $newVersion->id,
                    'new_version_number' => $newVersion->version_number,
                    'redirect_url' => route('wiki.show', $page->slug),
                ]);
            } else { // 如果是强制冲突保存
                Log::warning("Page {$page->id} conflict forced by user {$user->id}. New non-current version {$newVersion->version_number} created. Status: {$pageStatus}");

                return response()->json([
                    'status' => 'conflict_forced',
                    'message' => '您的更改已保存，但与当前版本存在冲突。页面已被锁定，请等待处理。',
                    'new_version_id' => $newVersion->id, // 返回创建的版本ID
                    'new_version_number' => $newVersion->version_number,
                    'redirect_url' => route('wiki.show', $page->slug),
                ]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error updating page {$page->id} by user {$user->id}: ".$e->getMessage()."\n".$e->getTraceAsString());

            return response()->json([
                'status' => 'error',
                'message' => '保存页面时发生内部错误，请稍后重试。',
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
            Log::error("Error soft deleting page {$page->id}: ".$e->getMessage());

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
                Log::info("Wiki page {$pageId} ('{$pageTitle}') permanently deleted by user ".Auth::id());
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
            Log::error("Error force deleting page {$pageId}: ".$e->getMessage());

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
                'comment' => '恢复自版本 '.$versionNumberToRevertTo,
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
        if (! $currentVersion || ! $conflictingVersion) {
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
            Log::error("Error resolving conflict for page {$page->id} by user {$user->id}: ".$e->getMessage()."\n".$e->getTraceAsString());

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
        if (! $success) {
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
            Log::error("Error sending discussion message for page {$page->id} by user {$user->id}: ".$e->getMessage());

            return response()->json(['success' => false, 'message' => '发送消息失败'], SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // 辅助方法：记录活动日志
    protected function logActivity(string $action, Model $subject, ?array $properties = null): void
    {
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
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
        $pseudoPage->categories = $categories->map(fn ($cat) => $cat->toArray())->toArray(); // 格式化分类
        $pseudoPage->tags = $tags->map(fn ($tag) => $tag->toArray())->toArray();       // 格式化标签
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
        if (! $user) {
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
            Log::error("Error deleting draft for page {$page->id} by user {$user->id}: ".$e->getMessage());

            return response()->json(['message' => '删除草稿时出错'], 500);
        }
    }
}

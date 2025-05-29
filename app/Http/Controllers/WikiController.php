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

/**
 * Wiki 页面控制器
 *
 * 负责 Wiki 页面的显示、创建、编辑、删除、版本管理、评论、协作及垃圾箱功能。
 */
class WikiController extends Controller
{
    /**
     * 显示 Wiki 页面列表。
     *
     * 支持根据分类、标签、状态和搜索关键词进行筛选。
     *
     * @param Request $request HTTP 请求实例。
     * @return InertiaResponse Inertia 响应，包含分页的页面列表和筛选数据。
     */
    public function index(Request $request): InertiaResponse
    {
        // 构建 Wiki 页面查询，预加载创建者、分类和标签信息。
        $query = WikiPage::with(['creator:id,name', 'categories:id,name,slug', 'tags:id,name,slug'])
            // 默认显示已发布和冲突状态的页面。
            ->whereIn('status', [WikiPage::STATUS_PUBLISHED, WikiPage::STATUS_CONFLICT]);

        // 应用分类筛选。
        if ($request->filled('category')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        // 应用标签筛选。
        if ($request->filled('tag')) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->where('slug', $request->tag);
            });
        }

        // 应用状态筛选，仅限 'published' 和 'conflict'。
        if ($request->filled('status') && in_array($request->status, [WikiPage::STATUS_PUBLISHED, WikiPage::STATUS_CONFLICT])) {
            $query->where('status', $request->status);
        }

        // 应用标题和 Slug 搜索筛选。
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // 排序：冲突页面优先，然后按更新时间降序。
        $query->orderByRaw("FIELD(status, '".WikiPage::STATUS_CONFLICT."', '".WikiPage::STATUS_PUBLISHED."') DESC")
            ->latest('updated_at');

        // 获取分页的页面数据，并传递查询参数。
        $pages = $query
            ->select('id', 'title', 'slug', 'created_by', 'status', 'created_at', 'updated_at', 'current_version_id')
            ->paginate(15)
            ->withQueryString();

        // 获取所有分类，并统计其关联的已发布/冲突页面数量。
        $categories = WikiCategory::withCount(['pages' => function ($query) {
            $query->whereIn('status', [WikiPage::STATUS_PUBLISHED, WikiPage::STATUS_CONFLICT]);
        }])
            ->orderBy('order')
            ->select('id', 'name', 'slug')
            ->get();

        // 获取所有标签，并统计其关联的已发布/冲突页面数量。
        $tags = WikiTag::withCount(['pages' => function ($query) {
            $query->whereIn('status', [WikiPage::STATUS_PUBLISHED, WikiPage::STATUS_CONFLICT]);
        }])
            ->select('id', 'name', 'slug')
            ->get();

        // 渲染 Wiki 页面列表视图。
        return Inertia::render('Wiki/Index', [
            'pages' => $pages,
            'categories' => $categories,
            'tags' => $tags,
            'filters' => $request->only(['category', 'tag', 'search', 'status']), // 传递所有活跃的筛选器。
            'flash' => session('flash'), // 传递闪存消息。
        ]);
    }

    /**
     * 显示单个 Wiki 页面详情。
     *
     * 根据 Slug 获取页面内容、版本信息、评论、锁状态等。
     *
     * @param string $slug 页面 Slug。
     * @return InertiaResponse Inertia 响应，包含页面所有详细信息。
     */
    public function show(string $slug): InertiaResponse
    {
        // 根据 Slug 查找页面，预加载创建者、分类、标签、当前版本和锁定者信息。
        $page = WikiPage::where('slug', $slug)
            ->with(['creator:id,name', 'categories:id,name,slug', 'tags:id,name,slug'])
            ->with(['currentVersion' => function ($query) {
                $query->select('id', 'wiki_page_id', 'content', 'created_at', 'created_by', 'version_number', 'comment')
                    ->with('creator:id,name');
            }])
            ->with(['locker:id,name'])
            ->firstOrFail(); // 未找到则抛出 404。

        // 获取页面最新的版本（即使在冲突状态下，current_version_id 可能不是最新版本）。
        $latestVersion = $page->versions()->orderBy('version_number', 'desc')->with('creator:id,name')->first();

        // 检查数据一致性：如果 current_version_id 存在但关联的版本记录丢失。
        if ($page->current_version_id && ! $page->currentVersion) {
            Log::error("数据一致性错误: WikiPage ID {$page->id} ('{$page->title}') 的 current_version_id={$page->current_version_id} 对应的 WikiVersion 未找到。");
        }

        // 加载评论及其回复，只显示未隐藏的评论。
        $page->load(['comments' => function ($query) {
            $query->where('is_hidden', false)
                ->whereNull('parent_id') // 只加载顶级评论。
                ->with(['user:id,name', 'replies' => function ($q) {
                    $q->where('is_hidden', false)->with('user:id,name')->latest('created_at'); // 预加载回复。
                }])
                ->latest('created_at');
        }]);

        // 获取页面锁定状态和锁定者信息。
        $isLocked = $page->isLocked();
        $lockedBy = $isLocked ? $page->locker : null;

        // 如果用户已登录，获取用户的草稿。
        $draft = null;
        if (Auth::check()) {
            $draft = WikiPageDraft::where('wiki_page_id', $page->id)
                ->where('user_id', Auth::id())
                ->select('content', 'last_saved_at')
                ->first();
        }

        // 判断当前用户是否可以编辑页面（非冲突状态且有权限且未被他人锁定）。
        $canEdit = Auth::check()
            && $page->status !== WikiPage::STATUS_CONFLICT
            && Gate::allows('update', $page);

        // 判断当前用户是否可以解决冲突（独立权限）。
        $canResolveConflict = Auth::check() && Gate::allows('resolveConflict', $page);

        // 渲染 Wiki 页面详情视图。
        return Inertia::render('Wiki/Show', [
            'page' => $page->toArray(),
            'currentVersion' => $latestVersion ? $latestVersion->toArray() : ($page->currentVersion ? $page->currentVersion->toArray() : null), // 优先显示最新版本内容，即使是冲突页面的非当前版本。
            'isLocked' => $isLocked,
            'lockedBy' => $lockedBy ? $lockedBy->only('id', 'name') : null,
            'draft' => $draft,
            'canEditPage' => $canEdit,
            'canResolveConflict' => $canResolveConflict,
            'isConflictPage' => $page->status === WikiPage::STATUS_CONFLICT, // 明确标记页面是否处于冲突状态。
            'error' => session('error') ?: ($page->current_version_id && ! $page->currentVersion ? '无法加载指定的内容版本，数据可能存在问题。' : null),
            'comments' => $page->comments->toArray(),
            'flash' => session('flash'),
            'isPreview' => false, // 标记为非预览模式。
        ]);
    }

    /**
     * 显示创建 Wiki 页面表单。
     *
     * @return InertiaResponse Inertia 响应，包含创建页面所需的分类和标签数据。
     */
    public function create(): InertiaResponse
    {
        // 授权检查：确保当前用户有权限创建 Wiki 页面。
        $this->authorize('create', WikiPage::class);

        // 获取所有分类，并按排序字段升序排列。
        $categories = WikiCategory::select('id', 'name')->orderBy('order')->get();
        // 获取所有标签。
        $tags = WikiTag::select('id', 'name')->get();

        // 渲染创建 Wiki 页面视图。
        return Inertia::render('Wiki/Create', [
            'categories' => $categories,
            'tags' => $tags,
        ]);
    }

    /**
     * 处理创建维基页面的请求。
     *
     * @param Request $request HTTP请求实例。
     * @return RedirectResponse 重定向响应。
     */
    public function store(Request $request): RedirectResponse
    {
        // 授权检查：确保当前用户有权限创建维基页面。
        $this->authorize('create', WikiPage::class);

        // 验证请求数据。
        $validated = $request->validate([
            'title' => 'required|string|max:255|unique:wiki_pages,title',
            'content' => 'required|string',
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'required|exists:wiki_categories,id',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:wiki_tags,id',
        ]);

        // 使用 HTML Purifier 清理 HTML 内容。
        $validated['content'] = Purifier::clean($validated['content']);

        // 生成页面 Slug，如果中文标题则使用拼音。
        $slug = Str::slug($validated['title']);
        if (empty($slug)) {
            $slug = Pinyin::permalink($validated['title'], '-');
        }

        // 处理 Slug 冲突，如果已存在则添加后缀。
        $originalSlug = $slug;
        $counter = 1;
        while (WikiPage::where('slug', $slug)->exists()) {
            $slug = $originalSlug.'-'.$counter++;
        }

        // 开启数据库事务。
        DB::beginTransaction();
        try {
            // 1. 创建 Wiki 页面，初始状态为草稿。
            $page = WikiPage::create([
                'title' => $validated['title'],
                'slug' => $slug,
                'status' => WikiPage::STATUS_DRAFT,
                'created_by' => Auth::id(),
                'current_version_id' => null,
            ]);
            // 2. 创建页面的第一个版本。
            $version = WikiVersion::create([
                'wiki_page_id' => $page->id,
                'content' => $validated['content'],
                'created_by' => Auth::id(),
                'version_number' => 1,
                'comment' => '初始版本',
                'is_current' => true,
            ]);
            // 3. 更新页面，将其状态设为已发布，并关联当前版本。
            $page->update([
                'current_version_id' => $version->id,
                'status' => WikiPage::STATUS_PUBLISHED,
            ]);
            // 4. 关联分类和标签。
            $page->categories()->attach($validated['category_ids']);
            if (! empty($validated['tag_ids'])) {
                $page->tags()->attach($validated['tag_ids']);
            }
            // 5. 记录活动日志。
            $this->logActivity('create', $page, ['version' => $version->version_number]);
            // 提交事务。
            DB::commit();

            Log::info("Wiki 页面 {$page->id} ('{$page->title}') 由用户 ".Auth::id()." 成功创建。");

            // 重定向并显示成功消息。
            return redirect()->route('wiki.show', $page->slug)
                ->with('flash', ['message' => ['type' => 'success', 'text' => '页面创建成功！']]);
        } catch (\Exception $e) {
            // 回滚事务并记录错误。
            DB::rollBack();
            Log::error('创建 Wiki 页面时出错: '.$e->getMessage());

            // 重定向并显示错误消息。
            return back()->withErrors(['general' => '创建页面时出错，请稍后重试。'])->withInput();
        }
    }

    /**
     * 显示 Wiki 页面编辑表单。
     *
     * 负责处理页面冲突和协作编辑的逻辑，并提供页面数据、草稿和权限信息。
     *
     * @param WikiPage $page Wiki 页面模型实例。
     * @param CollaborationService $collaborationService 协作服务实例。
     * @return InertiaResponse|RedirectResponse Inertia 响应或重定向响应。
     */
    public function edit(WikiPage $page, CollaborationService $collaborationService): InertiaResponse|RedirectResponse
    {
        // 基础更新权限检查。
        $this->authorize('update', $page);
        $user = Auth::user();

        // 页面冲突状态检查：如果页面处于冲突状态。
        if ($page->status === WikiPage::STATUS_CONFLICT) {
            // 如果用户没有解决冲突的权限，则重定向回页面详情页并提示错误。
            if (! $user?->can('wiki.resolve_conflict')) {
                Log::warning("用户 {$user?->id} 尝试访问冲突页面 {$page->id} 的编辑路由，但没有解决权限。");

                return redirect()->route('wiki.show', $page->slug)
                    ->with('flash', ['message' => ['type' => 'error', 'text' => '此页面处于冲突状态，您没有权限解决。']]);
            }
            // 如果用户有解决冲突的权限，则重定向到专门的冲突解决页面。
            Log::info("用户 {$user->id} 通过编辑路由访问冲突页面 {$page->id} 的冲突解决界面。重定向到冲突详情页。");

            return redirect()->route('wiki.show-conflicts', $page->slug);
        }

        // 非冲突页面编辑流程：协作编辑人数限制检查。
        $editors = $collaborationService->getEditors($page->id);
        $editorCount = count($editors);
        $isCurrentUserEditing = isset($editors[$user->id]);
        if ($editorCount >= 2 && ! $isCurrentUserEditing) {
            Log::warning("页面 {$page->id} 已达到编辑人数上限 (2)。拒绝用户 {$user->id} 的访问。");

            return redirect()->route('wiki.show', $page->slug)
                ->with('flash', ['message' => ['type' => 'error', 'text' => '此页面当前已有两人正在编辑，请稍后再试。']]);
        }

        // 通用可编辑性检查（例如页面是否被其他用户锁定）。
        $isEditable = Gate::allows('update', $page);
        if (! $isEditable) {
            // 如果页面被锁定，加载锁定者信息并重定向。
            $page->loadMissing('locker:id,name');
            $lockerName = $page->locker ? $page->locker->name : '未知用户';
            Log::warning("用户 {$user->id} 尝试编辑已锁定页面 {$page->id}。被 {$lockerName} 锁定。");

            return redirect()->route('wiki.show', $page->slug)
                ->with('flash', ['message' => ['type' => 'warning', 'text' => "页面当前被 {$lockerName} 锁定编辑中。"]]);
        }

        // 获取用户的草稿或当前版本内容。
        $draft = WikiPageDraft::where('wiki_page_id', $page->id)
            ->where('user_id', $user->id)
            ->select('content', 'last_saved_at')
            ->orderBy('last_saved_at', 'desc')
            ->first();

        // 预加载当前版本、分类和标签的ID。
        $page->load(['currentVersion:id,wiki_page_id,version_number,content', 'categories:id', 'tags:id']);

        // 如果存在草稿，使用草稿内容；否则使用页面当前版本内容。
        $content = $draft ? $draft->content : ($page->currentVersion ? $page->currentVersion->content : '');

        // 获取所有分类和标签，用于表单选择。
        $categories = WikiCategory::select('id', 'name')->orderBy('order')->get();
        $tags = WikiTag::select('id', 'name')->orderBy('name')->get();

        // 如果页面可编辑，则注册当前用户为页面编辑者（同时更新心跳）。
        if ($isEditable) {
            $collaborationService->registerEditor($page, $user);
        }

        // 准备传递给前端视图的数据。
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
            'editorIsEditable' => $isEditable,
            'errors' => session('errors') ? session('errors')->getBag('default')->getMessages() : (object) [],
            'flash' => session('flash'),
            'initialVersionId' => $page->current_version_id, // 记录用户开始编辑时所基于的页面版本ID。
            'initialVersionNumber' => $page->currentVersion?->version_number ?? 0, // 记录用户开始编辑时所基于的页面版本号。
        ];

        return Inertia::render('Wiki/Edit', $editPageData);
    }

    /**
     * 保存用户 Wiki 页面草稿。
     *
     * 接收 AJAX 请求，验证内容并保存或更新用户的页面草稿。
     *
     * @param Request $request HTTP 请求实例。
     * @param int $pageId 页面 ID。
     * @return JsonResponse JSON 响应，包含保存状态和信息。
     */
    public function saveDraft(Request $request, $pageId): JsonResponse
    {
        try {
            $page = WikiPage::findOrFail((int) $pageId); // 查找页面，如果不存在则抛出异常。
        } catch (ModelNotFoundException $e) {
            Log::warning("尝试为不存在或已删除的页面 ID: {$pageId} 保存草稿。");

            return response()->json(['message' => '页面不存在或已被删除'], SymfonyResponse::HTTP_NOT_FOUND);
        }

        $user = Auth::user(); // 获取当前认证用户。

        // 如果页面处于冲突状态，不允许保存草稿。
        if ($page->status === WikiPage::STATUS_CONFLICT) {
            Log::warning("页面 {$page->id} 处于冲突状态，用户 {$user->id} 无法保存草稿。");

            return response()->json(['message' => '页面处于冲突状态，无法保存草稿'], SymfonyResponse::HTTP_CONFLICT);
        }

        // 验证请求内容。
        $validated = $request->validate(['content' => 'required|string']);

        try {
            // 更新或创建草稿记录。
            $draft = WikiPageDraft::updateOrCreate(
                ['wiki_page_id' => $page->id, 'user_id' => $user->id],
                [
                    'content' => Purifier::clean($validated['content']), // 清理内容。
                    'last_saved_at' => now(), // 更新保存时间。
                ]
            );
            Log::info("页面 {$page->id} 的草稿由用户 {$user->id} 保存。草稿 ID: {$draft->id}");

            // 返回成功响应。
            return response()->json([
                'message' => '草稿已自动保存',
                'saved_at' => $draft->last_saved_at->toIso8601String(),
                'draft_id' => $draft->id,
                'success' => true,
            ]);
        } catch (\Exception $e) {
            // 记录错误并返回内部服务器错误响应。
            Log::error("用户 {$user->id} 保存页面 {$page->id} 草稿时出错: ".$e->getMessage());

            return response()->json(['message' => '保存草稿时出错', 'success' => false], SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 处理更新维基页面的请求。
     *
     * 负责版本冲突检测、新版本创建、页面状态更新及相关日志记录。
     *
     * @param Request $request HTTP请求实例。
     * @param WikiPage $page 要更新的维基页面模型实例。
     * @param CollaborationService $collaborationService 协作服务实例。
     * @param DiffService $diffService 差异服务实例。
     * @return JsonResponse|RedirectResponse JSON 响应（用于冲突或错误）或重定向响应（用于成功）。
     */
    public function update(Request $request, WikiPage $page, CollaborationService $collaborationService, DiffService $diffService): JsonResponse|RedirectResponse
    {
        // 授权检查：确保当前用户有权限更新此维基页面。
        $this->authorize('update', $page);
        $user = Auth::user();

        // 记录用户提交的基础版本ID，用于调试和并发控制。
        $submittedBaseVersionIdInput = $request->input('version_id');
        Log::info("WikiController@update: 页面 ID {$page->id}, 用户 ID {$user->id}。接收到的基础版本 ID 输入: {$submittedBaseVersionIdInput}。");

        // 验证请求数据。
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'required|exists:wiki_categories,id',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:wiki_tags,id',
            'comment' => 'nullable|string|max:255',
            'version_id' => [
                'nullable', 'integer',
                function ($attribute, $value, $fail) use ($page) {
                    if ($value !== null && $value !== 0 && ! WikiVersion::where('wiki_page_id', $page->id)->where('id', $value)->exists()) {
                        $fail('提交所基于的版本 ID 无效或不属于此页面。');
                    }
                },
            ],
            'force_conflict' => 'sometimes|boolean', // 强制冲突保存标志。
        ]);

        // 刷新页面模型以获取最新数据库状态，并加载关联关系。
        $page->refresh();
        $page->load(['currentVersion.creator:id,name', 'categories:id', 'tags:id']);

        // 清理提交的内容。
        $newContent = Purifier::clean($validated['content']);
        // 规范化提交的基础版本ID。
        $submittedBaseVersionId = ($validated['version_id'] === null || $validated['version_id'] === 0) ? null : (int) $validated['version_id'];
        // 获取强制冲突保存标志。
        $forceConflict = $request->boolean('force_conflict', false);
        // 记录页面在请求开始时的原始冲突状态。
        $isPageOriginallyInConflict = $page->status === WikiPage::STATUS_CONFLICT;

        // 获取数据库中当前版本的信息。
        $currentDbVersion = $page->currentVersion;
        $currentVersionIdInDb = $currentDbVersion?->id;

        Log::info("WikiController@update: 页面 ID {$page->id}。数据库当前版本 ID: {$currentVersionIdInDb}。提交的基础 ID: {$submittedBaseVersionId}。强制冲突标志: ".($forceConflict ? 'true' : 'false'));

        // 版本冲突检测：如果不是强制提交，且用户提交的基础版本与数据库当前版本不一致。
        if (! $forceConflict && $submittedBaseVersionId !== $currentVersionIdInDb && $submittedBaseVersionId !== null) {
            Log::warning("页面 {$page->id} 检测到过时版本。用户基础版本: {$submittedBaseVersionId}，数据库当前版本: {$currentVersionIdInDb}。用户: {$user->id}");

            // 尝试加载用户所基于的版本和数据库当前版本的内容以生成差异。
            $baseVersionForDiff = WikiVersion::find($submittedBaseVersionId);
            if (! $baseVersionForDiff || ! $currentDbVersion) {
                Log::error("无法为过时检查生成差异: 未找到页面 {$page->id} 的基础版本 {$submittedBaseVersionId} 或当前数据库版本 {$currentVersionIdInDb}。");

                return response()->json([
                    'status' => 'error',
                    'message' => '无法加载用于比较的版本信息，数据可能已发生变化，请刷新页面后重试。',
                    'current_version_id' => $currentVersionIdInDb,
                ], SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR);
            }

            // 生成并返回差异信息。
            $diffBaseVsCurrent = $diffService->generateDiffHtml($baseVersionForDiff->content, $currentDbVersion->content);
            $diffUserVsCurrent = $diffService->generateDiffHtml($newContent, $currentDbVersion->content);

            return response()->json([
                'status' => 'stale_version',
                'message' => '页面已被其他用户更新。请处理版本差异。',
                'current_version_id' => $currentVersionIdInDb,
                'current_version_number' => $currentDbVersion->version_number,
                'diff_base_vs_current' => $diffBaseVsCurrent,
                'diff_user_vs_current' => $diffUserVsCurrent,
                'current_content' => $currentDbVersion->content,
                'current_version_creator' => $currentDbVersion->creator->name ?? '未知用户',
                'current_version_updated_at' => $currentDbVersion->created_at->toIso8601String(),
            ], SymfonyResponse::HTTP_CONFLICT);
        }

        // 检查是否有实际内容、标题、分类或标签变化。
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

        // 如果没有实际变化，且不是强制提交，并且页面原本不是冲突状态，则直接返回。
        if (! $hasChanges && ! $forceConflict && ! $isPageOriginallyInConflict) {
            Log::info("用户 {$user->id} 保存页面 {$page->id} 时未检测到实际更改，跳过新版本创建。");
            WikiPageDraft::where('wiki_page_id', $page->id)->where('user_id', $user->id)->delete();
            $collaborationService->unregisterEditor($page, $user);

            return response()->json([
                'status' => 'no_changes',
                'message' => '未检测到更改，页面未更新。',
                'redirect_url' => route('wiki.show', $page->slug),
            ], SymfonyResponse::HTTP_OK);
        }

        // 开启数据库事务。
        DB::beginTransaction();
        try {
            $logProperties = [];
            // 获取新版本号（在锁定行后获取以确保唯一性）。
            $newVersionNumber = ($page->versions()->lockForUpdate()->latest('version_number')->value('version_number') ?? 0) + 1;
            $isNewVersionCurrent = true;
            $pageStatus = WikiPage::STATUS_PUBLISHED;
            $versionComment = $validated['comment'] ?: '更新页面';

            // 处理强制冲突保存。
            if ($forceConflict) {
                Log::warning("用户 {$user->id} 正在对页面 {$page->id} 执行强制冲突保存。基础版本: {$submittedBaseVersionId}，数据库当前版本: {$currentVersionIdInDb}");
                $pageStatus = WikiPage::STATUS_CONFLICT;
                $isNewVersionCurrent = false;
                $versionComment = $validated['comment'] ?: "强制提交版本（目标是已过时的 v{$currentDbVersion?->version_number}）";
                $logProperties['conflict_forced'] = true;
                $logProperties['db_version_at_conflict'] = $currentVersionIdInDb;
            }
            // 处理解决原有冲突。
            elseif ($isPageOriginallyInConflict) {
                Log::info("用户 {$user->id} 通过保存新版本来解决页面 {$page->id} 的冲突。");
                $pageStatus = WikiPage::STATUS_PUBLISHED;
                $versionComment = $validated['comment'] ?: '解决编辑冲突';
                $logProperties['conflict_resolved'] = true;
                if ($currentVersionIdInDb) {
                    WikiVersion::where('id', $currentVersionIdInDb)->update(['is_current' => false]);
                }
            }

            // 记录标题变化。
            if ($titleChanged) {
                $logProperties['title_changed'] = true;
            }

            // 创建新的版本记录。
            $newVersion = WikiVersion::create([
                'wiki_page_id' => $page->id,
                'content' => $newContent,
                'created_by' => $user->id,
                'version_number' => $newVersionNumber,
                'comment' => $versionComment,
                'is_current' => $isNewVersionCurrent,
            ]);
            $logProperties['version'] = $newVersionNumber;

            // 准备更新页面数据。
            $updateData = [
                'status' => $pageStatus,
                'title' => $validated['title'],
            ];

            // 如果新版本是当前版本，则更新页面的 current_version_id 并标记旧版本为非当前。
            if ($isNewVersionCurrent) {
                $updateData['current_version_id'] = $newVersion->id;
                if ($currentVersionIdInDb && $currentVersionIdInDb !== $newVersion->id && ! $forceConflict) {
                    $affected = WikiVersion::where('id', $currentVersionIdInDb)->where('is_current', true)->update(['is_current' => false]);
                    if ($affected === 0) {
                        Log::warning("WikiController@update: 尝试将旧版本 {$currentVersionIdInDb} 标记为非当前，但它未被标记为当前或不存在。");
                    }
                }
            }

            // 更新页面的锁定状态。
            if ($pageStatus === WikiPage::STATUS_CONFLICT) {
                $updateData['is_locked'] = true;
                $updateData['locked_by'] = null;
                $updateData['locked_until'] = null;
            } elseif ($isPageOriginallyInConflict && $pageStatus === WikiPage::STATUS_PUBLISHED) {
                $updateData['is_locked'] = false;
                $updateData['locked_by'] = null;
                $updateData['locked_until'] = null;
            } else {
                $updateData['is_locked'] = false;
                $updateData['locked_by'] = null;
                $updateData['locked_until'] = null;
            }
            // 执行页面更新。
            $page->update($updateData);

            // 同步分类和标签。
            if ($categoriesChanged) {
                $page->categories()->sync($validated['category_ids']);
                $logProperties['categories_changed'] = true;
            }
            if ($tagsChanged || $request->has('tag_ids')) {
                $page->tags()->sync($validated['tag_ids'] ?? []);
                $logProperties['tags_changed'] = true;
            }

            // 清理用户草稿并注销编辑状态。
            WikiPageDraft::where('wiki_page_id', $page->id)->where('user_id', $user->id)->delete();
            $collaborationService->unregisterEditor($page, $user);

            // 确定活动日志操作类型并记录。
            $logAction = 'update';
            if ($forceConflict) {
                $logAction = 'force_conflict_save';
            } elseif ($isPageOriginallyInConflict && $pageStatus === WikiPage::STATUS_PUBLISHED) {
                $logAction = 'conflict_resolved';
            }
            $this->logActivity($logAction, $page, $logProperties);
            DB::commit();

            // 根据保存结果返回不同 JSON 响应。
            if ($isNewVersionCurrent) {
                Log::info("页面 {$page->id} 由用户 {$user->id} 成功更新到版本 {$newVersion->version_number}。新当前版本 ID: {$newVersion->id}。状态: {$pageStatus}");
                try {
                    event(new WikiPageVersionUpdated($page->id, $newVersion->id));
                    Log::info("已为页面 {$page->id} 广播 WikiPageVersionUpdated 事件，新版本 ID: {$newVersion->id}");
                } catch (\Exception $e) {
                    Log::error("广播 WikiPageVersionUpdated 事件失败，页面 {$page->id}: ".$e->getMessage());
                }

                return response()->json([
                    'status' => $isPageOriginallyInConflict ? 'conflict_resolved' : 'success',
                    'message' => $isPageOriginallyInConflict ? '冲突已成功解决！' : '页面更新成功！',
                    'new_version_id' => $newVersion->id,
                    'new_version_number' => $newVersion->version_number,
                    'redirect_url' => route('wiki.show', $page->slug),
                ]);
            } else { // 强制冲突保存。
                Log::warning("页面 {$page->id} 由用户 {$user->id} 强制冲突保存。新创建非当前版本 {$newVersion->version_number}。状态: {$pageStatus}");

                return response()->json([
                    'status' => 'conflict_forced',
                    'message' => '您的更改已保存，但与当前版本存在冲突。页面已被锁定，请等待处理。',
                    'new_version_id' => $newVersion->id,
                    'new_version_number' => $newVersion->version_number,
                    'redirect_url' => route('wiki.show', $page->slug),
                ]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("用户 {$user->id} 更新页面 {$page->id} 时出错: ".$e->getMessage()."\n".$e->getTraceAsString());

            return response()->json([
                'status' => 'error',
                'message' => '保存页面时发生内部错误，请稍后重试。',
            ], SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 处理删除（软删除）维基页面的请求。
     *
     * 将指定的 Wiki 页面移动到回收站。
     *
     * @param WikiPage $page 要删除的维基页面模型实例。
     * @return RedirectResponse 重定向响应。
     */
    public function destroy(WikiPage $page): RedirectResponse
    {
        // 授权检查：确保当前用户有权限删除此维基页面。
        $this->authorize('delete', $page);
        try {
            $pageTitle = $page->title;
            $pageId = $page->id;
            $userId = Auth::id();

            // 执行软删除操作。
            if ($page->delete()) {
                // 记录活动日志。
                $this->logActivity('delete', $page, ['title' => $pageTitle, 'soft_deleted' => true]);
                Log::info("Wiki 页面 {$pageId} ('{$pageTitle}') 由用户 {$userId} 软删除。");

                // 重定向并显示成功消息。
                return redirect()->route('wiki.index')
                    ->with('flash', ['message' => ['type' => 'success', 'text' => '页面已移至回收站！']]);
            } else {
                // 如果软删除失败，记录错误。
                Log::error("用户 {$userId} 软删除页面 {$pageId} ('{$pageTitle}') 失败。");

                return back()->withErrors(['general' => '将页面移至回收站时出错，请稍后重试。']);
            }
        } catch (\Exception $e) {
            // 捕获异常，记录错误。
            Log::error("软删除页面 {$page->id} 时出错: ".$e->getMessage());

            return back()->withErrors(['general' => '删除页面时发生内部错误，请稍后重试。']);
        }
    }

    /**
     * 显示 Wiki 页面回收站列表。
     *
     * @param Request $request HTTP 请求实例。
     * @return InertiaResponse Inertia 响应，包含分页的已删除页面列表。
     */
    public function trashIndex(Request $request): InertiaResponse
    {
        // 授权检查：确保当前用户有权限查看回收站。
        $this->authorize('viewTrash', WikiPage::class);

        // 构建查询：只查询已软删除的页面，并预加载创建者、分类和标签信息。
        $query = WikiPage::onlyTrashed()
            ->with(['creator:id,name', 'categories:id,name,slug', 'tags:id,name,slug']);

        // 如果有搜索关键词，则应用搜索筛选。
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // 获取分页的已删除页面数据，按删除时间降序排列，并保留查询参数。
        $trashedPages = $query->latest('deleted_at')
            ->paginate(15)
            ->withQueryString();

        // 渲染 Wiki 回收站视图。
        return Inertia::render('Wiki/Trash/Index', [
            'trashedPages' => $trashedPages,
            'filters' => $request->only(['search']), // 传递当前搜索筛选器。
            'flash' => session('flash'), // 传递闪存消息。
        ]);
    }

    /**
     * 恢复回收站中的 Wiki 页面。
     *
     * @param int $pageId 要恢复的页面 ID。
     * @return RedirectResponse 重定向响应。
     */
    public function restore(int $pageId): RedirectResponse
    {
        // 授权检查：确保当前用户有权限恢复页面。
        $this->authorize('restore', WikiPage::class);

        try {
            // 查找仅在回收站中的页面。
            $page = WikiPage::onlyTrashed()->findOrFail($pageId);
            $this->authorize('restore', $page); // 再次执行策略授权，以防万一。

            // 执行恢复操作。
            $page->restore();
            // 记录活动日志。
            $this->logActivity('restore', $page);
            Log::info("Wiki 页面 {$page->id} ('{$page->title}') 由用户 ".Auth::id()." 从回收站恢复。");

            // 重定向并显示成功消息。
            return redirect()->route('wiki.trash.index')
                ->with('flash', ['message' => ['type' => 'success', 'text' => '页面已成功恢复！']]);
        } catch (ModelNotFoundException $e) {
            Log::warning("尝试恢复不存在或不在回收站中的页面 ID: {$pageId}。");

            return redirect()->route('wiki.trash.index')
                ->withErrors(['general' => '无法找到要恢复的页面。']);
        } catch (\Exception $e) {
            Log::error("从回收站恢复页面 {$pageId} 时出错: ".$e->getMessage());

            return redirect()->route('wiki.trash.index')
                ->withErrors(['general' => '恢复页面时出错，请稍后重试。']);
        }
    }

    /**
     * 永久删除回收站中的 Wiki 页面。
     *
     * @param int $pageId 要永久删除的页面 ID。
     * @return RedirectResponse 重定向响应。
     */
    public function forceDelete(int $pageId): RedirectResponse
    {
        // 授权检查：确保当前用户有权限永久删除页面。
        $this->authorize('forceDelete', WikiPage::class);

        try {
            // 查找仅在回收站中的页面。
            $page = WikiPage::onlyTrashed()->findOrFail($pageId);
            $this->authorize('forceDelete', $page); // 再次执行策略授权。

            $pageTitle = $page->title;

            // 开启数据库事务，确保操作的原子性。
            DB::beginTransaction();
            try {
                // 执行强制删除。
                $page->forceDelete();
                // 记录活动日志。
                $this->logActivity('force_delete', $page, ['title' => $pageTitle]);
                Log::info("Wiki 页面 {$pageId} ('{$pageTitle}') 由用户 ".Auth::id()." 永久删除。");
                // 提交事务。
                DB::commit();

                // 重定向并显示成功消息。
                return redirect()->route('wiki.trash.index')
                    ->with('flash', ['message' => ['type' => 'success', 'text' => '页面已永久删除！']]);
            } catch (\Exception $e) {
                // 回滚事务。
                DB::rollBack();
                throw $e; // 将异常抛出给外层处理。
            }
        } catch (ModelNotFoundException $e) {
            Log::warning("尝试永久删除不存在或不在回收站中的页面 ID: {$pageId}。");

            return redirect()->route('wiki.trash.index')
                ->withErrors(['general' => '无法找到要永久删除的页面。']);
        } catch (\Exception $e) {
            Log::error("永久删除页面 {$pageId} 时出错: ".$e->getMessage());

            return redirect()->route('wiki.trash.index')
                ->withErrors(['general' => '永久删除页面时出错，请稍后重试。']);
        }
    }

    /**
     * 显示特定 Wiki 页面历史版本的内容。
     *
     * @param WikiPage $page Wiki 页面模型实例。
     * @param int $versionNumber 要显示的 Wiki 版本号。
     * @return InertiaResponse Inertia 响应，包含特定版本的内容和页面基本信息。
     */
    public function showVersion(WikiPage $page, int $versionNumber): InertiaResponse
    {
        // 授权检查：确保当前用户有权限查看该 Wiki 页面的历史版本。
        $this->authorize('viewHistory', $page);

        // 查询指定 Wiki 页面和版本号的历史版本记录，并预加载创建者信息。
        $versionRecord = WikiVersion::where('wiki_page_id', $page->id)
            ->where('version_number', $versionNumber)
            ->with('creator:id,name')
            ->firstOrFail(); // 如果找不到匹配的版本则自动抛出 404 异常。

        // 预加载页面的创建者、分类和标签信息，用于在视图中显示。
        $page->load(['creator:id,name', 'categories:id,name,slug', 'tags:id,name,slug']);

        // 渲染 'Wiki/ShowVersion' 视图，并传递页面和版本数据。
        return Inertia::render('Wiki/ShowVersion', [
            'page' => $page->only('id', 'title', 'slug', 'creator', 'categories', 'tags'), // 仅传递页面 ID、标题、Slug、创建者、分类和标签。
            'version' => $versionRecord->only('id', 'version_number', 'content', 'comment', 'created_at', 'creator'), // 仅传递版本 ID、版本号、内容、评论、创建时间及创建者。
        ]);
    }

    /**
     * 显示 Wiki 页面历史版本记录。
     *
     * @param WikiPage $page Wiki 页面模型实例。
     * @return InertiaResponse Inertia 响应，包含页面历史数据。
     */
    public function history(WikiPage $page): InertiaResponse
    {
        // 授权检查：确保当前用户有权限查看该 Wiki 页面的历史版本。
        $this->authorize('viewHistory', $page);
        // 查询指定 Wiki 页面的所有历史版本。
        $versions = WikiVersion::where('wiki_page_id', $page->id)
            // 预加载创建者信息（只选择 id 和 name 字段，避免加载不必要的数据）。
            ->with('creator:id,name')
            // 按照版本号降序排列，最新版本在前。
            ->orderBy('version_number', 'desc')
            // 只选择需要的字段，优化查询性能。
            ->select('id', 'version_number', 'comment', 'created_at', 'created_by', 'is_current')
            // 对查询结果进行分页，每页显示 15 条记录。
            ->paginate(15);
        // 使用 Inertia.js 渲染 'Wiki/History' 视图。
        return Inertia::render('Wiki/History', [
            // 传递 Wiki 页面的一些基本信息（只选择 id, title, slug 字段）。
            'page' => $page->only('id', 'title', 'slug'),
            // 传递查询到的所有历史版本数据（已分页）。
            'versions' => $versions,
        ]);
    }

    /**
     * 比较 Wiki 页面两个历史版本的内容差异。
     *
     * @param WikiPage $page Wiki 页面模型实例。
     * @param int $fromVersionNumber 起始版本号。
     * @param int $toVersionNumber 结束版本号。
     * @return InertiaResponse|RedirectResponse Inertia 响应（包含差异）或重定向响应（版本号相同）。
     */
    public function compareVersions(WikiPage $page, int $fromVersionNumber, int $toVersionNumber): InertiaResponse|RedirectResponse
    {
        // 授权检查：确保当前用户有权限查看页面历史。
        $this->authorize('viewHistory', $page);

        // 如果选择的版本号相同，则重定向并提示。
        if ($fromVersionNumber === $toVersionNumber) {
            return redirect()->route('wiki.history', $page->slug)
                ->with('flash', ['message' => ['type' => 'warning', 'text' => '请选择两个不同的版本进行比较。']]);
        }

        // 获取起始版本和结束版本的详细信息。
        $fromVersion = WikiVersion::where('wiki_page_id', $page->id)
            ->where('version_number', $fromVersionNumber)
            ->with('creator:id,name')
            ->firstOrFail();

        $toVersion = WikiVersion::where('wiki_page_id', $page->id)
            ->where('version_number', $toVersionNumber)
            ->with('creator:id,name')
            ->firstOrFail();

        // 使用 DiffService 生成 HTML 格式的内容差异。
        $diffService = app(DiffService::class);
        $diffHtml = $diffService->generateDiffHtml($fromVersion->content, $toVersion->content);

        // 确定哪个是旧版本，哪个是新版本，以便于视图展示。
        $olderVersion = $fromVersion->version_number < $toVersion->version_number ? $fromVersion : $toVersion;
        $newerVersion = $fromVersion->version_number > $toVersion->version_number ? $fromVersion : $toVersion;

        // 渲染版本比较视图。
        return Inertia::render('Wiki/Compare', [
            'page' => $page->only('id', 'title', 'slug'),
            'fromVersion' => $olderVersion->only('id', 'version_number', 'content', 'comment', 'created_at', 'creator'),
            'toVersion' => $newerVersion->only('id', 'version_number', 'content', 'comment', 'created_at', 'creator'),
            'fromCreator' => $olderVersion->creator, // 方便前端直接访问创建者信息。
            'toCreator' => $newerVersion->creator,
            'diffHtml' => $diffHtml,
        ]);
    }

    /**
     * 将Wiki页面恢复到指定的历史版本。
     *
     * 该方法会创建一个新的版本，其内容为指定历史版本的内容，并将其设置为当前版本。
     *
     * @param Request $request HTTP请求实例。
     * @param WikiPage $page 要恢复的Wiki页面实例。
     * @param int $versionNumberToRevertTo 要恢复到的目标版本号。
     * @return RedirectResponse 恢复成功或失败后的重定向响应。
     */
    public function revertToVersion(Request $request, WikiPage $page, int $versionNumberToRevertTo): RedirectResponse
    {
        // 授权检查：确保当前用户有权限恢复此页面。
        $this->authorize('revert', $page);
        $user = Auth::user();

        // 查找要恢复到的历史版本记录。
        $versionToRevert = WikiVersion::where('wiki_page_id', $page->id)
            ->where('version_number', $versionNumberToRevertTo)
            ->firstOrFail();

        // 如果页面当前处于冲突状态，则不允许执行版本恢复操作。
        if ($page->status === WikiPage::STATUS_CONFLICT) {
            return back()->withErrors(['general' => '无法恢复版本，页面当前处于冲突状态，请先解决冲突。']);
        }

        // 启动数据库事务。
        DB::beginTransaction();
        try {
            // 获取当前页面最新版本号，用于新版本。
            $latestVersionNumber = $page->versions()->latest('version_number')->value('version_number') ?? 0;
            // 创建新的版本记录。
            $newVersion = WikiVersion::create([
                'wiki_page_id' => $page->id,
                'content' => $versionToRevert->content,
                'created_by' => $user->id,
                'version_number' => $latestVersionNumber + 1,
                'comment' => '恢复自版本 '.$versionNumberToRevertTo,
                'is_current' => true,
            ]);
            // 将页面原有的当前版本标记为非当前。
            if ($page->current_version_id) {
                WikiVersion::where('id', $page->current_version_id)->update(['is_current' => false]);
            }
            // 更新 Wiki 页面将其当前版本指向新创建的版本。
            $page->update(['current_version_id' => $newVersion->id]);
            // 记录恢复页面的活动日志。
            $this->logActivity('revert', $page, [
                'reverted_to_version' => $versionNumberToRevertTo,
                'new_version' => $newVersion->version_number,
            ]);
            // 提交事务。
            DB::commit();

            Log::info("页面 {$page->id} 由用户 {$user->id} 恢复到版本 {$versionNumberToRevertTo}。新版本: {$newVersion->version_number}");

            // 重定向并显示成功提示。
            return redirect()->route('wiki.show', $page->slug)
                ->with('flash', ['message' => ['type' => 'success', 'text' => '页面已恢复到版本 '.$versionNumberToRevertTo]]);
        } catch (\Exception $e) {
            // 回滚事务并记录错误。
            DB::rollBack();
            Log::error("用户 {$user->id} 恢复页面 {$page->id} 到版本 {$versionNumberToRevertTo} 时出错: ".$e->getMessage());

            // 返回上一个页面并显示错误提示。
            return back()->withErrors(['general' => '恢复版本时出错，请稍后重试。']);
        }
    }

    /**
     * 显示 Wiki 页面编辑冲突详情。
     *
     * 该方法用于展示处于冲突状态的页面的两个冲突版本，并生成内容差异。
     *
     * @param WikiPage $page Wiki 页面模型实例。
     * @param DiffService $diffService 差异服务实例。
     * @return InertiaResponse|RedirectResponse Inertia 响应（包含冲突详情）或重定向响应。
     */
    public function showConflicts(WikiPage $page, DiffService $diffService): InertiaResponse|RedirectResponse
    {
        // 授权检查：确保当前用户有权限解决冲突。
        $this->authorize('resolveConflict', $page);

        // 确保页面确实处于冲突状态。
        if ($page->status !== WikiPage::STATUS_CONFLICT) {
            Log::info("尝试显示非冲突页面 {$page->id} 的冲突。");

            return redirect()->route('wiki.show', $page->slug)
                ->with('flash', ['message' => ['type' => 'info', 'text' => '该页面当前没有冲突需要解决。']]);
        }

        // 获取“当前”版本（通过 current_version_id 关联，通常是导致冲突的版本）。
        $currentVersion = $page->currentVersion()->with('creator:id,name')->first();

        // 获取另一个“冲突”版本（即非当前版本中最新创建的）。
        $conflictingVersion = $page->versions()
            ->where('is_current', false)
            ->orderBy('version_number', 'desc')
            ->with('creator:id,name')
            ->first();

        // 如果未能获取到必要的版本信息，记录错误并重定向。
        if (! $currentVersion || ! $conflictingVersion) {
            Log::error("页面 {$page->id} 解决冲突时出错: 缺少当前版本或冲突版本数据。");

            return redirect()->route('wiki.show', $page->slug)
                ->withErrors(['general' => '无法加载冲突版本信息，请联系管理员。']);
        }

        // 生成两个冲突版本之间的内容差异 HTML。
        $diffHtml = $diffService->generateDiffHtml($conflictingVersion->content, $currentVersion->content);

        // 渲染冲突解决视图。
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

    /**
     * 解决维基页面的编辑冲突。
     *
     * 该方法处理用户提交的冲突解决方案，创建一个新的页面版本，并更新页面状态。
     *
     * @param Request $request HTTP请求，包含解决后的内容和评论。
     * @param WikiPage $page 需要解决冲突的维基页面实例。
     * @return RedirectResponse 重定向响应，表示操作结果。
     */
    public function resolveConflict(Request $request, WikiPage $page): RedirectResponse
    {
        // 授权检查：确保当前用户有权限解决此页面的冲突。
        $this->authorize('resolveConflict', $page);
        $user = Auth::user();

        // 检查页面当前状态是否为冲突状态。
        if ($page->status !== WikiPage::STATUS_CONFLICT) {
            Log::info("提交的冲突解决方案针对页面 {$page->id}，但该页面当前并非冲突状态。重定向。");

            return redirect()->route('wiki.show', $page->slug)
                ->with('flash', ['message' => ['type' => 'info', 'text' => '该页面当前没有冲突需要解决。']]);
        }

        // 验证用户提交的请求数据。
        $validated = $request->validate([
            'content' => 'required|string',
            'resolution_comment' => 'nullable|string|max:255',
        ]);

        // 开启数据库事务，确保操作的原子性。
        DB::beginTransaction();
        try {
            // 清理用户提交的内容，防止XSS攻击等安全问题。
            $cleanContent = Purifier::clean($validated['content']);
            // 获取页面最新的版本号，用于生成新版本号。
            $latestVersionNumber = $page->versions()->where('wiki_page_id', $page->id)->latest('version_number')->value('version_number') ?? 0;

            // 1. 创建一个新的 Wiki 页面版本，保存用户解决冲突后的内容。
            $newVersion = WikiVersion::create([
                'wiki_page_id' => $page->id,
                'content' => $cleanContent,
                'created_by' => $user->id,
                'version_number' => $latestVersionNumber + 1,
                'comment' => $validated['resolution_comment'] ?: '解决编辑冲突',
                'is_current' => true, // 标记此新版本为当前版本。
            ]);
            // 2. 将此页面所有其他版本标记为非当前版本，确保唯一性。
            WikiVersion::where('wiki_page_id', $page->id)
                ->where('id', '!=', $newVersion->id)
                ->update(['is_current' => false]);
            // 3. 更新页面的当前版本ID、状态，并解除页面锁定。
            $page->update([
                'current_version_id' => $newVersion->id,
                'status' => WikiPage::STATUS_PUBLISHED, // 将页面状态改回已发布。
                'is_locked' => false, // 解除页面锁定。
                'locked_by' => null,
                'locked_until' => null,
            ]);

            // 记录冲突解决活动日志。
            if (method_exists($page, 'logCustomActivity')) {
                $page->logCustomActivity('conflict_resolved', ['resolved_by' => $user->id, 'new_version' => $newVersion->version_number]);
            } else {
                ActivityLog::log('conflict_resolved', $page, ['resolved_by' => $user->id, 'new_version' => $newVersion->version_number]);
            }

            // 提交数据库事务。
            DB::commit();
            Log::info("页面 {$page->id} 的冲突由用户 {$user->id} 解决。新当前版本: {$newVersion->version_number}");

            // 重定向到页面详情页，并显示成功消息。
            return redirect()->route('wiki.show', $page->slug)
                ->with('flash', ['message' => ['type' => 'success', 'text' => '页面冲突已成功解决！']]);
        } catch (\Exception $e) {
            // 发生异常时回滚数据库事务。
            DB::rollBack();
            Log::error("用户 {$user->id} 解决页面 {$page->id} 冲突时出错: ".$e->getMessage()."\n".$e->getTraceAsString());

            // 返回上一页并显示错误信息。
            return back()->withErrors(['general' => '解决冲突时发生内部错误，请稍后重试。'])->withInput();
        }
    }

    /**
     * 获取 Wiki 页面的活跃编辑者列表（通过 AJAX）。
     *
     * @param WikiPage $page Wiki 页面实例。
     * @param CollaborationService $collaborationService 协作服务实例。
     * @return JsonResponse JSON 响应，包含编辑者列表。
     */
    public function getEditors(WikiPage $page, CollaborationService $collaborationService): JsonResponse
    {
        // 调用协作服务获取指定页面的活跃编辑者。
        $editors = $collaborationService->getEditors($page->id);

        // 返回包含编辑者列表的 JSON 响应。
        return response()->json(['editors' => array_values($editors)]);
    }

    /**
     * 注册当前用户为 Wiki 页面编辑者或更新其活跃状态（通过 AJAX）。
     *
     * @param Request $request HTTP 请求实例。
     * @param WikiPage $page Wiki 页面实例。
     * @param CollaborationService $collaborationService 协作服务实例。
     * @return JsonResponse JSON 响应，指示操作是否成功。
     */
    public function registerEditor(Request $request, WikiPage $page, CollaborationService $collaborationService): JsonResponse
    {
        $user = Auth::user(); // 获取当前认证用户。
        // 调用协作服务注册编辑者或更新其活跃心跳。
        $success = $collaborationService->registerEditor($page, $user);

        // 如果注册失败（例如达到编辑人数上限或页面临时锁定），返回 429 错误。
        if (! $success) {
            return response()->json(['success' => false, 'message' => '已达到编辑人数上限或页面暂时锁定'], 429);
        }

        // 返回成功响应。
        return response()->json(['success' => true, 'message' => '已注册为页面编辑者或心跳已更新']);
    }

    /**
     * 注销当前用户在 Wiki 页面的编辑状态（通过 AJAX）。
     *
     * @param Request $request HTTP 请求实例。
     * @param WikiPage $page Wiki 页面实例。
     * @param CollaborationService $collaborationService 协作服务实例。
     * @return JsonResponse JSON 响应，指示操作是否成功。
     */
    public function unregisterEditor(Request $request, WikiPage $page, CollaborationService $collaborationService): JsonResponse
    {
        $user = Auth::user(); // 获取当前认证用户。
        // 调用协作服务注销用户在指定页面的编辑状态。
        $collaborationService->unregisterEditor($page, $user);

        // 返回成功响应。
        return response()->json(['success' => true, 'message' => '已注销编辑状态']);
    }

    /**
     * 获取 Wiki 页面的实时讨论消息（通过 AJAX）。
     *
     * @param WikiPage $page Wiki 页面实例。
     * @param CollaborationService $collaborationService 协作服务实例。
     * @return JsonResponse JSON 响应，包含讨论消息列表。
     */
    public function getDiscussionMessages(WikiPage $page, CollaborationService $collaborationService): JsonResponse
    {
        // 调用协作服务获取指定页面的讨论消息。
        $messages = $collaborationService->getDiscussionMessages($page->id);

        // 返回包含消息列表的 JSON 响应。
        return response()->json(['messages' => $messages]);
    }

    /**
     * 发送 Wiki 页面的实时讨论消息（通过 AJAX）。
     *
     * @param Request $request HTTP 请求实例，包含消息内容。
     * @param WikiPage $page Wiki 页面实例。
     * @param CollaborationService $collaborationService 协作服务实例。
     * @return JsonResponse JSON 响应，指示操作是否成功。
     */
    public function sendDiscussionMessage(Request $request, WikiPage $page, CollaborationService $collaborationService): JsonResponse
    {
        // 验证消息内容。
        $validated = $request->validate(['message' => 'required|string|max:500']);
        $user = Auth::user(); // 获取当前认证用户。

        try {
            // 调用协作服务添加讨论消息。
            $message = $collaborationService->addDiscussionMessage($page, $user, $validated['message']);

            // 返回成功响应。
            return response()->json(['success' => true, 'message' => $message]);
        } catch (\Exception $e) {
            // 记录错误并返回内部服务器错误响应。
            Log::error("用户 {$user->id} 发送页面 {$page->id} 讨论消息时出错: ".$e->getMessage());

            return response()->json(['success' => false, 'message' => '发送消息失败'], SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 辅助方法：记录活动日志。
     *
     * 记录指定操作、主题模型和额外属性的活动日志。
     * 在控制台或单元测试环境中运行时，会跳过日志记录。
     *
     * @param string $action 操作类型（例如 'create', 'update', 'delete'）。
     * @param Model $subject 被操作的主题模型实例。
     * @param array|null $properties 额外属性数组，默认为 null。
     * @return void
     */
    protected function logActivity(string $action, Model $subject, ?array $properties = null): void
    {
        // 如果在控制台运行且不是单元测试，则跳过日志记录。
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return;
        }
        try {
            // 调用 ActivityLog 模型的静态方法记录日志。
            ActivityLog::log($action, $subject, $properties);
        } catch (\Exception $e) {
            // 记录日志失败时的错误信息。
            Log::error("记录活动日志失败: 操作={$action}, 主题={$subject->getTable()}:{$subject->getKey()}, 错误: ".$e->getMessage());
        }
    }

    /**
     * 显示 Wiki 页面预览。
     *
     * 接收表单数据，清理内容并生成一个模拟的页面对象，然后渲染 Show 视图进行实时预览。
     *
     * @param Request $request HTTP 请求实例，包含标题、内容、分类和标签 ID。
     * @return InertiaResponse Inertia 响应，以预览模式渲染 Show 视图。
     */
    public function preview(Request $request): InertiaResponse
    {
        $user = Auth::user(); // 获取当前用户，可能为 null（用于预览创建者）。

        // 验证请求数据。
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:wiki_categories,id',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'integer|exists:wiki_tags,id',
        ]);

        // 清理 HTML 内容，确保安全。
        $cleanContent = Purifier::clean($validated['content']);

        // 获取选中的分类和标签的名称及 Slug，用于在预览中显示。
        $categories = WikiCategory::whereIn('id', $validated['category_ids'] ?? [])->select('id', 'name', 'slug')->get();
        $tags = WikiTag::whereIn('id', $validated['tag_ids'] ?? [])->select('id', 'name', 'slug')->get();

        // 创建一个模拟的页面对象，以匹配 Show 视图的预期数据结构。
        $pseudoPage = new \stdClass;
        $pseudoPage->id = 0; // 预览页面 ID 为 0。
        $pseudoPage->title = $validated['title'];
        $pseudoPage->slug = 'preview'; // 固定的预览 Slug。
        $pseudoPage->creator = $user ? $user->only('id', 'name') : ['id' => 0, 'name' => '预览用户']; // 模拟创建者信息。
        $pseudoPage->created_at = now();
        $pseudoPage->updated_at = now();
        $pseudoPage->categories = $categories->map(fn ($cat) => $cat->toArray())->toArray();
        $pseudoPage->tags = $tags->map(fn ($tag) => $tag->toArray())->toArray();
        $pseudoPage->status = 'preview'; // 标记为预览状态。
        $pseudoPage->currentVersion = null; // 版本信息将在伪版本对象中提供。

        // 创建一个模拟的版本对象。
        $pseudoVersion = new \stdClass;
        $pseudoVersion->id = 0; // 预览版本 ID 为 0。
        $pseudoVersion->content = $cleanContent;
        $pseudoVersion->creator = $pseudoPage->creator;
        $pseudoVersion->created_at = now();
        $pseudoVersion->version_number = '预览'; // 版本号显示为“预览”。
        $pseudoVersion->comment = '实时预览内容';
        $pseudoVersion->is_current = true;

        // 渲染 'Wiki/Show' 视图，并传入预览相关的数据。
        return Inertia::render('Wiki/Show', [
            'page' => (array) $pseudoPage, // 将模拟页面对象转换为数组。
            'currentVersion' => (array) $pseudoVersion, // 将模拟版本对象转换为数组。
            'isLocked' => false, // 预览页面始终不锁定。
            'lockedBy' => null,
            'draft' => null, // 预览页面无草稿。
            'canEditPage' => false, // 预览页面不可编辑。
            'canResolveConflict' => false, // 预览页面无冲突。
            'error' => null,
            'comments' => [], // 预览页面不显示评论。
            'flash' => null, // 预览页面无闪存消息。
            'isPreview' => true, // 明确标记为预览模式。
        ]);
    }

    /**
     * 删除当前用户的 Wiki 页面草稿（通过 AJAX）。
     *
     * @param Request $request HTTP 请求实例。
     * @param WikiPage $page Wiki 页面实例。
     * @return JsonResponse JSON 响应，指示操作是否成功。
     */
    public function deleteMyDraft(Request $request, WikiPage $page): JsonResponse
    {
        $user = Auth::user(); // 获取当前认证用户。
        if (! $user) {
            return response()->json(['message' => '未认证'], 401);
        }
        try {
            // 删除指定页面和用户的草稿。
            $deletedCount = WikiPageDraft::where('wiki_page_id', $page->id)
                ->where('user_id', $user->id)
                ->delete();

            if ($deletedCount > 0) {
                Log::info("用户 {$user->id} 删除了页面 {$page->id} 的草稿。");
                // 删除草稿后，注销用户的编辑状态。
                app(CollaborationService::class)->unregisterEditor($page, $user);

                return response()->json(['message' => '草稿已成功删除'], 200);
            } else {
                Log::info("用户 {$user->id} 尝试删除页面 {$page->id} 的草稿，但未找到。");
                // 即使未找到草稿，也尝试注销编辑状态，以确保状态同步。
                app(CollaborationService::class)->unregisterEditor($page, $user);

                return response()->json(['message' => '未找到您的草稿'], 200);
            }
        } catch (\Exception $e) {
            // 记录错误并返回内部服务器错误响应。
            Log::error("用户 {$user->id} 删除页面 {$page->id} 草稿时出错: ".$e->getMessage());

            return response()->json(['message' => '删除草稿时出错'], 500);
        }
    }
}
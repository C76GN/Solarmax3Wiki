<?php
namespace App\Http\Controllers;
use App\Models\WikiPage;
use App\Models\WikiPageIssue;
use App\Models\User;
use App\Repositories\WikiPageRepository;
use App\Http\Requests\StoreWikiPageRequest;
use App\Http\Requests\UpdateWikiPageRequest;
use App\Services\WikiContentService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
class WikiPageController extends Controller
{
    protected $repository;
    protected $contentService;
    public function __construct(WikiPageRepository $repository, WikiContentService $contentService)
    {
        $this->repository = $repository;
        $this->contentService = $contentService;
    }
    
    /**
     * 提交页面问题
     *
     * @param Request $request 请求对象
     * @return RedirectResponse 重定向响应
     */
    public function issue(Request $request): RedirectResponse
    {
        // 验证请求数据
        $validated = $request->validate([
            'page_id' => 'required',
            'content' => 'required|string'
        ]);
        // 创建页面问题记录
        WikiPageIssue::create([
            'wiki_page_id' => $validated['page_id'],
            'reported_by' => auth()->id(),
            'content' => $this->contentService->purifyContent($validated['content']),
            'status' => 'to_be_solved',
        ]);
        return redirect()->route('wiki.show', ['page' => $validated['page_id']])
            ->with('flash', ['message' => ['type' => 'success', 'text' => '提交成功']]);
    }
    /**
     * 处理页面问题
     *
     * @param Request $request 请求对象
     * @return RedirectResponse 重定向响应
     */
    public function issue_handle(Request $request): RedirectResponse
    {
        // 验证请求数据
        $validated = $request->validate([
            'id' => 'required|exists:wiki_page_issues,id',
        ]);
        // 更新问题状态为已处理
        $issue = WikiPageIssue::find($validated['id']);
        $issue->update(['status' => WikiPageIssue::STATUS_HANDLED]);
        return redirect()->route('wiki.show', ['page' => $issue->wiki_page_id])
            ->with('flash', ['message' => ['type' => 'success', 'text' => '提交成功']]);
    }
    /**
     * 显示创建页面的表单
     *
     * @return Response|RedirectResponse 视图响应或重定向响应
     */
    public function create(): Response|RedirectResponse
    {
        // 检查权限
        if (!auth()->user()->hasPermission('wiki.create')) {
            return $this->unauthorized();
        }
        // 获取所有分类
        $categories = $this->repository->getAllCategories()
            ->map(fn($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'description' => $category->description
            ]);
        return Inertia::render('Wiki/Create', ['categories' => $categories]);
    }
    /**
     * 显示页面列表
     *
     * @param Request $request 请求对象
     * @return Response 视图响应
     */
    public function index(Request $request): Response
    {
        // 获取当前用户
        $currentUser = $request->user();
        $userId = $currentUser?->id ?? 0;
        
        // 获取页面列表
        $pages = $this->repository->getPagesList(
            $request->only(['search', 'status', 'category', 'sort']),
            $userId
        );
        // 格式化页面数据
        $pages->through(fn($page) => $this->formatPageData($page));
        // 获取所有分类
        $categories = $this->repository->getAllCategories()->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'pages_count' => $category->pages_count,
            ];
        });
        // 获取用户权限
        $userPermissions = [
            'create_page' => $currentUser?->hasPermission('wiki.create') ?? false,
            'edit_page' => $currentUser?->hasPermission('wiki.edit') ?? false,
            'delete_page' => $currentUser?->hasPermission('wiki.delete') ?? false,
            'show_status' => $currentUser?->hasPermission('wiki.status_show') ?? false,
            'manage_trash' => $currentUser?->hasPermission('wiki.manage_trash') ?? false
        ];
        return Inertia::render('Wiki/Index', [
            'pages' => $pages,
            'categories' => $categories,
            'uid' => $userId,
            'filters' => $request->only(['search', 'status', 'category', 'sort']),
            'can' => $userPermissions,
        ]);
    }
    /**
     * 格式化页面数据用于列表显示
     *
     * @param WikiPage $page 页面实例
     * @return array 格式化后的页面数据
     */
    private function formatPageData(WikiPage $page): array
    {
        return [
            'id' => $page->id,
            'title' => $page->title,
            'status' => $page->status,
            'created_by' => $page->created_by,
            'creator' => $page->creator ? ['name' => $page->creator->name] : null,
            'lastEditor' => $page->lastEditor ? ['name' => $page->lastEditor->name] : null,
            'categories' => $page->categories->map(fn($category) => [
                'id' => $category->id,
                'name' => $category->name
            ]),
            'published_at' => optional($page->published_at)->toDateTimeString(),
            'view_count' => $page->view_count,
            'created_at' => optional($page->created_at)->toDateTimeString(),
        ];
    }
    /**
     * 存储新创建的页面
     *
     * @param StoreWikiPageRequest $request 创建页面请求
     * @return RedirectResponse 重定向响应
     */
    public function store(StoreWikiPageRequest $request): RedirectResponse
    {
        // 获取验证过的数据
        $validated = $request->validated();
        // 净化内容并添加其他必要字段
        $validated['content'] = $this->contentService->purifyContent($validated['content']);
        $validated['slug'] = $this->repository->generateUniqueSlug($validated['title']);
        $validated['created_by'] = auth()->id();
        $validated['last_edited_by'] = auth()->id();
        try {
            DB::beginTransaction();
            // 创建页面
            $page = WikiPage::create($validated);
            // 关联分类
            if (!empty($validated['categories'])) {
                $page->categories()->sync($validated['categories']);
            }
            // 更新引用关系并创建初始版本
            $page->updateReferences();
            $page->createRevision('初始版本');
            DB::commit();
            return redirect()->route('wiki.index')->with('flash', [
                'message' => ['type' => 'success', 'text' => '页面创建成功！']
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => '页面创建失败: ' . $e->getMessage()]);
        }
    }
    /**
     * 获取页面当前状态
     *
     * @param WikiPage $page 页面实例
     * @param Request $request 请求对象
     * @return JsonResponse JSON响应
     */
    public function getPageStatus(WikiPage $page, Request $request): JsonResponse
    {
        // 检查用户认证
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        // 检查页面是否已被修改
        $lastCheck = $request->query('last_check');
        $hasBeenModified = false;
        if ($lastCheck) {
            $hasBeenModified = $page->updated_at->greaterThan($lastCheck);
        }
        // 获取当前正在编辑的用户
        $currentEditors = Cache::get("editing_page:{$page->id}", []);
        $currentUser = auth()->id();
        $activeEditors = [];
        $usernames = [];
        // 处理当前编辑用户列表
        foreach ($currentEditors as $userId => $timestamp) {
            if ($userId != $currentUser && now()->diffInMinutes($timestamp) < 10) {
                $activeEditors[$userId] = $timestamp;
                $user = User::find($userId);
                if ($user) {
                    $usernames[] = $user->name;
                }
            }
        }
        return response()->json([
            'hasBeenModified' => $hasBeenModified,
            'currentEditors' => $usernames,
            'lastModified' => $page->updated_at
        ]);
    }
    /**
     * 通知系统用户正在编辑页面
     *
     * @param WikiPage $page 页面实例
     * @return JsonResponse JSON响应
     */
    public function notifyEditing(WikiPage $page): JsonResponse
    {
        $userId = auth()->id();
        $currentEditors = Cache::get("editing_page:{$page->id}", []);
        $currentEditors[$userId] = now();
        Cache::put("editing_page:{$page->id}", $currentEditors, now()->addMinutes(30));
        return response()->json(['success' => true]);
    }
    /**
     * 通知系统用户停止编辑页面
     *
     * @param WikiPage $page 页面实例
     * @return JsonResponse JSON响应
     */
    public function notifyStoppedEditing(WikiPage $page): JsonResponse
    {
        $userId = auth()->id();
        $currentEditors = Cache::get("editing_page:{$page->id}", []);
        if (isset($currentEditors[$userId])) {
            unset($currentEditors[$userId]);
        }
        if (count($currentEditors) > 0) {
            Cache::put("editing_page:{$page->id}", $currentEditors, now()->addMinutes(30));
        } else {
            Cache::forget("editing_page:{$page->id}");
        }
        return response()->json(['success' => true]);
    }
    /**
     * 显示编辑页面的表单
     *
     * @param WikiPage $page 页面实例
     * @return Response|RedirectResponse 视图响应或重定向响应
     */
    public function edit(WikiPage $page): Response|RedirectResponse
    {
        // 检查权限
        if (!auth()->user()->hasPermission('wiki.edit')) {
            return $this->unauthorized();
        }
        // 加载页面分类关系
        $page->load(['categories']);
        // 获取所有分类
        $categories = $this->repository->getAllCategories()->map(fn($category) => [
            'id' => $category->id,
            'name' => $category->name,
            'description' => $category->description
        ]);
        return Inertia::render('Wiki/Edit', [
            'page' => array_merge(
                $page->toArray(),
                ['categories' => $page->categories->pluck('id')->toArray()]
            ),
            'categories' => $categories,
            'canEdit' => true
        ]);
    }
    /**
     * 显示页面详情
     *
     * @param WikiPage $page 页面实例
     * @param Request $request 请求对象
     * @return Response 视图响应
     */
    public function show(WikiPage $page, Request $request): Response
    {
        // 增加页面浏览计数
        $page->incrementViewCount();
        // 获取页面问题列表
        $issuesQuery = WikiPageIssue::query()->where("wiki_page_id", $page->id);
        if ($request->has('filter') && $request->filter === 'unresolved') {
            $issuesQuery->where('status', 'to_be_solved');
        }
        $issues = $issuesQuery->orderBy("created_at", 'desc')->get();
        $headers = $this->extractPageHeaders($page->content);
        $currentUser = auth()->user();
        return Inertia::render('Wiki/Show', [
            'page' => array_merge(
                $page->load([
                    'creator', 'lastEditor', 'categories', 'referencedPages', 'referencedByPages'
                ])->toArray(),
                [
                    'can' => [
                        'edit_page' => $currentUser?->hasPermission('wiki.edit'),
                        'issue' => $currentUser?->hasPermission('wiki.issue')
                    ],
                    'issue' => $issues->toArray(),
                    'related_pages' => $page->getRelatedPages(),
                    'is_following' => $page->isFollowedByUser($currentUser?->id),
                    'references_count' => $page->incomingReferences()->count(),
                    'recent_revisions' => $page->revisions()->with('creator:id,name')->latest()->take(5)->get(['id', 'version', 'created_by']),
                    'headers' => $headers,
                ]
            )
        ]);
    }
    private function extractPageHeaders(?string $content): array
    {
        $headers = [];
        if ($content) {
            preg_match_all('/<h([2-4])[^>]*id="([^"]+)"[^>]*>(.*?)<\/h\1>/i', $content, $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                $headers[] = [
                    'level' => (int)$match[1],
                    'id' => $match[2],
                    'text' => strip_tags($match[3])
                ];
            }
        }
        return $headers;
    }

    /**
     * 锁定页面，防止编辑冲突
     */
    public function lockPage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page_id' => 'required|exists:wiki_pages,id',
            'reason' => 'nullable|string'
        ]);
        
        $pageId = $validated['page_id'];
        $reason = $validated['reason'] ?? '发现编辑冲突';
        $userId = auth()->id();
        
        // 设置锁定信息，包括谁锁定的，原因，锁定时间
        $lockData = [
            'user_id' => $userId,
            'reason' => $reason,
            'locked_at' => now()->toDateTimeString(),
            'expires_at' => now()->addHours(2)->toDateTimeString() // 锁定2小时后自动过期
        ];
        
        Cache::put("page_locked:{$pageId}", $lockData, now()->addHours(2));
        
        // 记录锁定活动
        $page = WikiPage::findOrFail($pageId);
        ActivityLog::log('lock', $page, $lockData);
        
        return response()->json([
            'success' => true,
            'message' => '页面已锁定，其他用户暂时无法编辑'
        ]);
    }

    /**
     * 解锁页面，允许编辑
     */
    public function unlockPage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page_id' => 'required|exists:wiki_pages,id'
        ]);
        
        $pageId = $validated['page_id'];
        $userId = auth()->id();
        
        // 检查是否有权限解锁
        $lockData = Cache::get("page_locked:{$pageId}");
        if (!$lockData) {
            return response()->json([
                'success' => false,
                'message' => '页面未被锁定'
            ]);
        }
        
        // 只有锁定者或管理员可以解锁
        if ($lockData['user_id'] != $userId && !auth()->user()->hasPermission('wiki.unlock_any')) {
            return response()->json([
                'success' => false,
                'message' => '您没有权限解锁此页面'
            ], 403);
        }
        
        Cache::forget("page_locked:{$pageId}");
        
        // 记录解锁活动
        $page = WikiPage::findOrFail($pageId);
        ActivityLog::log('unlock', $page, [
            'unlocked_by' => $userId,
            'previously_locked_by' => $lockData['user_id']
        ]);
        
        return response()->json([
            'success' => true,
            'message' => '页面已解锁，可以继续编辑'
        ]);
    }

    /**
     * 检查页面是否被锁定
     */
    public function checkPageLock(int $pageId): JsonResponse
    {
        $lockData = Cache::get("page_locked:{$pageId}");
        
        if (!$lockData) {
            return response()->json([
                'locked' => false
            ]);
        }
        
        // 检查锁是否已过期
        if (now()->isAfter($lockData['expires_at'])) {
            Cache::forget("page_locked:{$pageId}");
            return response()->json([
                'locked' => false
            ]);
        }
        
        // 获取锁定用户信息
        $user = User::find($lockData['user_id']);
        $userName = $user ? $user->name : '未知用户';
        
        return response()->json([
            'locked' => true,
            'locked_by' => $userName,
            'reason' => $lockData['reason'],
            'locked_at' => $lockData['locked_at'],
            'expires_at' => $lockData['expires_at'],
            // 当前用户是否可以解锁
            'can_unlock' => auth()->id() == $lockData['user_id'] || auth()->user()->hasPermission('wiki.unlock_any')
        ]);
    }
    public function update(UpdateWikiPageRequest $request, WikiPage $page): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();
        $validated['content'] = $this->contentService->purifyContent($validated['content']);
        $validated['last_edited_by'] = auth()->id();
        $validated['status'] = WikiPage::STATUS_PENDING;
        
        // 获取内容差异以增强冲突检测
        $contentDiff = $this->contentService->calculateDiff($page->content, $validated['content']);
        $hasSignificantChanges = $this->contentService->hasSignificantChanges($contentDiff);

        // 检查编辑冲突
        if (!$request->has('force_update')) {
            $lastUpdated = $page->updated_at;
            if ($request->has('last_check') && $lastUpdated->greaterThan($request->input('last_check'))) {
                // 检查是否有人正在编辑此页面
                $currentEditors = Cache::get("editing_page:{$page->id}", []);
                $otherEditors = collect($currentEditors)->except(auth()->id())->keys()->toArray();
                
                return response()->json([
                    'conflict' => true,
                    'message' => '页面已被他人修改，请刷新后重试或选择强制更新',
                    'editors' => $otherEditors,
                    'hasSignificantChanges' => $hasSignificantChanges,
                    'diff' => $contentDiff
                ], 409);
            }
        }

        try {
            DB::beginTransaction();
            $page->update($validated);
            $page->categories()->sync($validated['categories'] ?? []);
            $page->updateReferences();
            $page->createRevision($request->input('comment'));
            
            // 如果存在冲突但强制更新，记录此事件
            if ($request->has('force_update') && $request->force_update) {
                ActivityLog::log('force_update', $page, [
                    'conflict_resolved' => true,
                    'resolved_by' => auth()->id()
                ]);
            }
            
            DB::commit();
            
            // 如果有冲突并成功解决，解除页面锁定
            if ($request->has('force_update') && $request->force_update) {
                Cache::forget("page_locked:{$page->id}");
            }
            
            $this->removeFromEditingList($page->id, auth()->id());
            return redirect()->route('wiki.index')->with('flash', [
                'message' => ['type' => 'success', 'text' => '页面更新成功！']
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => '页面更新失败: ' . $e->getMessage()]);
        }
    }
    private function removeFromEditingList(int $pageId, int $userId): void
    {
        $currentEditors = Cache::get("editing_page:{$pageId}", []);
        if (isset($currentEditors[$userId])) {
            unset($currentEditors[$userId]);
            if (count($currentEditors) > 0) {
                Cache::put("editing_page:{$pageId}", $currentEditors, now()->addMinutes(30));
            } else {
                Cache::forget("editing_page:{$pageId}");
            }
        }
    }
    public function destroy(WikiPage $page): RedirectResponse
    {
        if (!auth()->user()->hasPermission('wiki.delete')) {
            return $this->unauthorized();
        }
        try {
            DB::transaction(function () use ($page) {
                $page->outgoingReferences()->delete();
                $page->incomingReferences()->delete();
                $page->delete();
            });
            return redirect()->route('wiki.index')->with('flash', [
                'message' => ['type' => 'success', 'text' => '页面删除成功！']
            ]);
        } catch (Exception $e) {
            return back()->withErrors(['error' => '页面删除失败: ' . $e->getMessage()]);
        }
    }
    public function publish(WikiPage $page): RedirectResponse
    {
        if (!auth()->user()->hasPermission('wiki.publish')) {
            return $this->unauthorized();
        }
        $page->update([
            'status' => WikiPage::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
        return redirect()->route('wiki.index')->with('flash', [
            'message' => ['type' => 'success', 'text' => '页面发布成功！']
        ]);
    }
    public function trash(Request $request): Response|RedirectResponse
    {
        if (!auth()->user()->hasPermission('wiki.manage_trash')) {
            return $this->unauthorized();
        }
        $query = WikiPage::onlyTrashed()
            ->with(['creator', 'lastEditor', 'categories']);
        $this->applyTrashFilters($query, $request);
        $trashedPages = $query->paginate(10)
            ->through(fn($page) => $this->formatTrashedPageData($page));
        $stats = $this->getTrashStats();
        return Inertia::render('Wiki/Trash', [
            'pages' => $trashedPages,
            'filters' => $request->only(['search', 'sort']),
            'stats' => $stats,
        ]);
    }
    private function applyTrashFilters($query, Request $request): void
    {
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where('title', 'like', "%{$search}%");
        }
        switch ($request->input('sort', 'deleted_at_desc')) {
            case 'deleted_at_asc':
                $query->orderBy('deleted_at', 'asc');
                break;
            case 'title':
                $query->orderBy('title', 'asc');
                break;
            case 'title_desc':
                $query->orderBy('title', 'desc');
                break;
            case 'deleted_at_desc':
            default:
                $query->orderBy('deleted_at', 'desc');
                break;
        }
    }
    private function formatTrashedPageData(WikiPage $page): array
    {
        return [
            'id' => $page->id,
            'title' => $page->title,
            'creator' => $page->creator ? ['name' => $page->creator->name] : null,
            'lastEditor' => $page->lastEditor ? ['name' => $page->lastEditor->name] : null,
            'categories' => $page->categories->map(fn($category) => [
                'id' => $category->id,
                'name' => $category->name
            ]),
            'deleted_at' => $page->deleted_at->format('Y-m-d H:i:s'),
        ];
    }
    private function getTrashStats(): array
    {
        $trashedQuery = WikiPage::onlyTrashed();
        $oldestDate = $trashedQuery->min('deleted_at');
        $newestDate = $trashedQuery->max('deleted_at');
        return [
            'total' => $trashedQuery->count(),
            'oldest_deleted_at' => $oldestDate ? date('Y-m-d', strtotime($oldestDate)) : null,
            'newest_deleted_at' => $newestDate ? date('Y-m-d', strtotime($newestDate)) : null,
        ];
    }
    public function restoreSelected(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:wiki_pages,id'
        ]);
        $count = 0;
        foreach ($validated['ids'] as $id) {
            try {
                $this->repository->restorePage($id);
                $count++;
            } catch (Exception $e) {
                Log::error('恢复页面失败: ' . $e->getMessage(), ['id' => $id]);
            }
        }
        return redirect()->back()->with('flash', [
            'message' => [
                'type' => 'success',
                'text' => "已成功恢复 {$count} 个页面"
            ]
        ]);
    }
    public function forceDeleteSelected(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:wiki_pages,id'
        ]);
        $count = 0;
        foreach ($validated['ids'] as $id) {
            try {
                $this->repository->forceDeletePage($id);
                $count++;
            } catch (Exception $e) {
                Log::error('永久删除页面失败: ' . $e->getMessage(), ['id' => $id]);
            }
        }
        return redirect()->back()->with('flash', [
            'message' => [
                'type' => 'success',
                'text' => "已永久删除 {$count} 个页面"
            ]
        ]);
    }
    public function restore(int $id): RedirectResponse
    {
        if (!auth()->user()->hasPermission('wiki.manage_trash')) {
            return $this->unauthorized();
        }
        try {
            $this->repository->restorePage($id);
            return redirect()->back()->with('flash', [
                'message' => ['type' => 'success', 'text' => '页面已成功恢复！']
            ]);
        } catch (Exception $e) {
            return redirect()->back()->with('flash', [
                'message' => ['type' => 'error', 'text' => '恢复失败：' . $e->getMessage()]
            ]);
        }
    }
    public function forceDelete(int $id): RedirectResponse
    {
        if (!auth()->user()->hasPermission('wiki.manage_trash')) {
            return $this->unauthorized();
        }
        try {
            $this->repository->forceDeletePage($id);
            return redirect()->back()->with('flash', [
                'message' => ['type' => 'success', 'text' => '页面已彻底删除！']
            ]);
        } catch (Exception $e) {
            return redirect()->back()->with('flash', [
                'message' => ['type' => 'error', 'text' => '删除失败：' . $e->getMessage()]
            ]);
        }
    }
    public function toggleFollow(WikiPage $page): JsonResponse
    {
        $user = auth()->user();
        $isFollowing = $page->isFollowedByUser($user->id);
        $isFollowing
            ? $page->followers()->detach($user->id)
            : $page->followers()->attach($user->id);
        return response()->json([
            'followed' => !$isFollowing,
            'message' => $isFollowing ? '取消关注成功' : '关注成功'
        ]);
    }
}
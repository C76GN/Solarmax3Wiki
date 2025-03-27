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

/**
 * Wiki页面控制器
 * 
 * 负责处理Wiki页面的CRUD操作、页面审核、问题报告、
 * 页面历史管理、回收站管理等功能
 */
class WikiPageController extends Controller
{
    /**
     * Wiki页面仓库
     *
     * @var WikiPageRepository
     */
    protected $repository;
    
    /**
     * Wiki内容服务
     *
     * @var WikiContentService
     */
    protected $contentService;

    /**
     * 构造函数
     *
     * @param WikiPageRepository $repository Wiki页面仓库
     * @param WikiContentService $contentService Wiki内容服务
     */
    public function __construct(WikiPageRepository $repository, WikiContentService $contentService)
    {
        $this->repository = $repository;
        $this->contentService = $contentService;
    }

    /**
     * 审核Wiki页面
     *
     * @param Request $request 请求对象
     * @return RedirectResponse 重定向响应
     */
    public function audit(Request $request): RedirectResponse
    {
        // 验证请求数据
        $validated = $request->validate([
            'id' => 'required|exists:wiki_pages,id',
            'status' => 'required|string|in:published,draft,pending,audit_failure',
            'status_message' => 'nullable|string|max:255'
        ]);
        
        // 更新页面状态
        WikiPage::where('id', $validated['id'])->update([
            'status' => $validated['status'],
            'status_message' => $validated['status_message'] ?? "",
            'published_at' => $validated['status'] === 'published' ? now() : null,
        ]);
        
        return redirect()->route('wiki.index')->with('flash', [
            'message' => ['type' => 'success', 'text' => '审核状态更新成功']
        ]);
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
        $canAudit = $currentUser?->hasPermission('wiki.page_audit') ?? false;
        
        // 获取页面列表
        $pages = $this->repository->getPagesList(
            $request->only(['search', 'status', 'category', 'sort']),
            $userId,
            $canAudit
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
            'audit_page' => $canAudit,
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
            'status_message' => $page->status_message,
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
        
        // 提取页面标题
        $headers = $this->extractPageHeaders($page->content);
        
        // 获取当前用户
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
    
    /**
     * 从页面内容中提取标题结构
     *
     * @param string|null $content 页面内容
     * @return array 标题结构数组
     */
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
     * 更新页面内容
     *
     * @param UpdateWikiPageRequest $request 更新页面请求
     * @param WikiPage $page 页面实例
     * @return RedirectResponse|JsonResponse 重定向响应或JSON响应
     */
    public function update(UpdateWikiPageRequest $request, WikiPage $page): RedirectResponse|JsonResponse
    {
        // 获取验证过的数据
        $validated = $request->validated();
        
        // 净化内容并添加其他必要字段
        $validated['content'] = $this->contentService->purifyContent($validated['content']);
        $validated['last_edited_by'] = auth()->id();
        $validated['status'] = WikiPage::STATUS_PENDING;
        
        // 检查更新冲突
        if (!$request->has('force_update')) {
            $lastUpdated = $page->updated_at;
            if ($request->has('last_check') && $lastUpdated->greaterThan($request->input('last_check'))) {
                return response()->json([
                    'conflict' => true,
                    'message' => '页面已被他人修改，请刷新后重试或选择强制更新'
                ], 409);
            }
        }
        
        try {
            DB::beginTransaction();
            
            // 更新页面
            $page->update($validated);
            
            // 同步分类关系
            $page->categories()->sync($validated['categories'] ?? []);
            
            // 更新引用关系并创建新版本
            $page->updateReferences();
            $page->createRevision($request->input('comment'));
            
            DB::commit();
            
            // 移除当前用户的编辑状态
            $this->removeFromEditingList($page->id, auth()->id());
            
            return redirect()->route('wiki.index')->with('flash', [
                'message' => ['type' => 'success', 'text' => '页面更新成功！']
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => '页面更新失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 从编辑列表中移除用户
     *
     * @param int $pageId 页面ID
     * @param int $userId 用户ID
     * @return void
     */
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

    /**
     * 删除页面（软删除）
     *
     * @param WikiPage $page 页面实例
     * @return RedirectResponse 重定向响应
     */
    public function destroy(WikiPage $page): RedirectResponse
    {
        // 检查权限
        if (!auth()->user()->hasPermission('wiki.delete')) {
            return $this->unauthorized();
        }
        
        try {
            DB::transaction(function () use ($page) {
                // 删除相关引用
                $page->outgoingReferences()->delete();
                $page->incomingReferences()->delete();
                
                // 软删除页面
                $page->delete();
            });
            
            return redirect()->route('wiki.index')->with('flash', [
                'message' => ['type' => 'success', 'text' => '页面删除成功！']
            ]);
        } catch (Exception $e) {
            return back()->withErrors(['error' => '页面删除失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 发布页面
     *
     * @param WikiPage $page 页面实例
     * @return RedirectResponse 重定向响应
     */
    public function publish(WikiPage $page): RedirectResponse
    {
        // 检查权限
        if (!auth()->user()->hasPermission('wiki.publish')) {
            return $this->unauthorized();
        }
        
        // 更新页面状态为已发布
        $page->update([
            'status' => WikiPage::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
        
        return redirect()->route('wiki.index')->with('flash', [
            'message' => ['type' => 'success', 'text' => '页面发布成功！']
        ]);
    }

    /**
     * 显示回收站页面
     *
     * @param Request $request 请求对象
     * @return Response|RedirectResponse 视图响应或重定向响应
     */
    public function trash(Request $request): Response|RedirectResponse
    {
        // 检查权限
        if (!auth()->user()->hasPermission('wiki.manage_trash')) {
            return $this->unauthorized();
        }

        // 构建查询
        $query = WikiPage::onlyTrashed()
            ->with(['creator', 'lastEditor', 'categories']);

        // 应用过滤条件
        $this->applyTrashFilters($query, $request);

        // 获取分页结果并格式化数据
        $trashedPages = $query->paginate(10)
            ->through(fn($page) => $this->formatTrashedPageData($page));
        
        // 获取统计信息
        $stats = $this->getTrashStats();

        return Inertia::render('Wiki/Trash', [
            'pages' => $trashedPages,
            'filters' => $request->only(['search', 'sort']),
            'stats' => $stats,
        ]);
    }
    
    /**
     * 应用回收站过滤条件
     *
     * @param \Illuminate\Database\Eloquent\Builder $query 查询构建器
     * @param Request $request 请求对象
     * @return void
     */
    private function applyTrashFilters($query, Request $request): void
    {
        // 添加搜索功能
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where('title', 'like', "%{$search}%");
        }

        // 添加排序功能
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
    
    /**
     * 格式化已删除页面数据
     *
     * @param WikiPage $page 页面实例
     * @return array 格式化后的页面数据
     */
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
    
    /**
     * 获取回收站统计信息
     *
     * @return array 统计信息
     */
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

    /**
     * 批量恢复页面
     *
     * @param Request $request 请求对象
     * @return RedirectResponse 重定向响应
     */
    public function restoreSelected(Request $request): RedirectResponse
    {
        // 验证请求数据
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
                // 记录错误但继续处理
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

    /**
     * 批量永久删除页面
     *
     * @param Request $request 请求对象
     * @return RedirectResponse 重定向响应
     */
    public function forceDeleteSelected(Request $request): RedirectResponse
    {
        // 验证请求数据
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
                // 记录错误但继续处理
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

    /**
     * 恢复单个页面
     *
     * @param int $id 页面ID
     * @return RedirectResponse 重定向响应
     */
    public function restore(int $id): RedirectResponse
    {
        // 检查权限
        if (!auth()->user()->hasPermission('wiki.manage_trash')) {
            return $this->unauthorized();
        }
        
        try {
            // 恢复页面
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

    /**
     * 永久删除单个页面
     *
     * @param int $id 页面ID
     * @return RedirectResponse 重定向响应
     */
    public function forceDelete(int $id): RedirectResponse
    {
        // 检查权限
        if (!auth()->user()->hasPermission('wiki.manage_trash')) {
            return $this->unauthorized();
        }
        
        try {
            // 永久删除页面
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

    /**
     * 切换关注状态
     *
     * @param WikiPage $page 页面实例
     * @return JsonResponse JSON响应
     */
    public function toggleFollow(WikiPage $page): JsonResponse
    {
        $user = auth()->user();
        $isFollowing = $page->isFollowedByUser($user->id);
        
        // 切换关注状态
        $isFollowing 
            ? $page->followers()->detach($user->id) 
            : $page->followers()->attach($user->id);
        
        return response()->json([
            'followed' => !$isFollowing,
            'message' => $isFollowing ? '取消关注成功' : '关注成功'
        ]);
    }
}
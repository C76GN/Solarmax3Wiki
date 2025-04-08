<?php

namespace App\Http\Controllers;

use App\Models\WikiPage;
use App\Models\WikiVersion;
use App\Models\WikiCategory;
use App\Models\WikiTag;
use App\Models\WikiPageDraft;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Mews\Purifier\Facades\Purifier;
use App\Models\ActivityLog;
use App\Services\DiffService;
use App\Services\CollaborationService;
use App\Models\WikiTemplate;

class WikiController extends Controller
{
    // 显示Wiki页面列表
    public function index(Request $request): Response
    {
        $query = WikiPage::with(['creator', 'categories', 'tags'])
            ->where('status', WikiPage::STATUS_PUBLISHED);
            
        // 应用过滤条件
        if ($request->has('category')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }
        
        if ($request->has('tag')) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->where('slug', $request->tag);
            });
        }
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%");
                // 实际项目中，可以添加对版本内容的全文搜索
            });
        }
        
        $pages = $query->latest()
            ->paginate(15)
            ->withQueryString();
            
        $categories = WikiCategory::withCount('pages')->get();
        $tags = WikiTag::withCount('pages')->get();
        
        return Inertia::render('Wiki/Index', [
            'pages' => $pages,
            'categories' => $categories,
            'tags' => $tags,
            'filters' => $request->only(['category', 'tag', 'search'])
        ]);
    }
    
    // 显示特定Wiki页面
    public function show(WikiPage $page): Response
    {
        if ($page->status === WikiPage::STATUS_CONFLICT && !auth()->user()?->hasPermission('wiki.resolve_conflict')) {
            return Inertia::render('Wiki/Conflict', [
                'page' => $page->load('currentVersion'),
                'message' => '此页面当前存在编辑冲突，需要管理员解决。'
            ]);
        }
        
        $page->load([
            'currentVersion',
            'creator',
            'categories',
            'tags',
            'template', // 加载模板信息
            'comments' => function($query) {
                $query->whereNull('parent_id')
                    ->with(['user', 'replies.user'])
                    ->latest();
            }
        ]);
        
        $isLocked = $page->isLocked();
        $lockedBy = $isLocked ? $page->locker : null;
        $draft = null;
        
        if (auth()->check()) {
            $draft = WikiPageDraft::where('wiki_page_id', $page->id)
                ->where('user_id', auth()->id())
                ->first();
        }
        
        // 获取所有模板
        $templates = [];
        if (auth()->user()?->hasPermission('wiki.create')) {
            $templates = WikiTemplate::all();
        }
        
        return Inertia::render('Wiki/Show', [
            'page' => $page,
            'isLocked' => $isLocked,
            'lockedBy' => $lockedBy,
            'draft' => $draft,
            'canEdit' => auth()->user()?->hasPermission('wiki.edit'),
            'canResolveConflict' => auth()->user()?->hasPermission('wiki.resolve_conflict'),
            'templates' => $templates
        ]);
    }
    
    // 显示创建页面表单
    public function create(): Response
    {
        
        $categories = WikiCategory::all();
        $tags = WikiTag::all();
        
        return Inertia::render('Wiki/Create', [
            'categories' => $categories,
            'tags' => $tags
        ]);
    }
    
    // 存储新页面
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category_ids' => 'required|array',
            'category_ids.*' => 'exists:wiki_categories,id',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:wiki_tags,id',
            'parent_id' => 'nullable|exists:wiki_pages,id',
            'template_id' => 'nullable|exists:wiki_templates,id',
            'template_fields' => 'nullable|array'
        ]);

        $validated['content'] = Purifier::clean($validated['content']);
        
        $slug = Str::slug($validated['title']);
        if (WikiPage::where('slug', $slug)->exists()) {
            $slug = $slug . '-' . Str::random(5);
        }
        
        try {
            DB::beginTransaction();
            
            // 创建页面
            $page = WikiPage::create([
                'title' => $validated['title'],
                'slug' => $slug,
                'status' => WikiPage::STATUS_DRAFT,
                'created_by' => auth()->id(),
                'parent_id' => $validated['parent_id'] ?? null,
                'template_id' => $validated['template_id'] ?? null,
                'meta' => $validated['template_fields'] ?? null, // 保存模板字段数据
            ]);
            
            // 创建版本
            $version = WikiVersion::create([
                'wiki_page_id' => $page->id,
                'content' => $validated['content'],
                'created_by' => auth()->id(),
                'version_number' => 1,
                'is_current' => true,
            ]);
            
            // 更新页面状态
            $page->update([
                'current_version_id' => $version->id,
                'status' => WikiPage::STATUS_PUBLISHED
            ]);
            
            // 分配分类和标签
            $page->categories()->attach($validated['category_ids']);
            if (!empty($validated['tag_ids'])) {
                $page->tags()->attach($validated['tag_ids']);
            }
            
            // 记录活动日志
            ActivityLog::log('create', $page, [
                'template_used' => $validated['template_id'] ?? null
            ]);
            
            DB::commit();
            
            return redirect()->route('wiki.show', $page->slug)
                ->with('flash', [
                    'message' => [
                        'type' => 'success',
                        'text' => '页面创建成功！'
                    ]
                ]);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    // 显示编辑页面表单
    public function edit(WikiPage $page)
    {
        
        // 如果页面处于冲突状态，仅允许有解决冲突权限的用户访问
        if ($page->status === WikiPage::STATUS_CONFLICT && !auth()->user()->hasPermission('wiki.resolve_conflict')) {
            return redirect()->route('wiki.show', $page->slug)
                ->with('flash', [
                    'message' => [
                        'type' => 'error',
                        'text' => '该页面存在编辑冲突，需要管理员解决。'
                    ]
                ]);
        }
        
        // 检查页面是否被锁定
        if ($page->isLocked()) {
            // 如果不是当前用户锁定的，拒绝访问
            if ($page->locked_by !== auth()->id()) {
                return redirect()->route('wiki.show', $page->slug)
                    ->with('flash', [
                        'message' => [
                            'type' => 'error',
                            'text' => '该页面正在被 ' . $page->locker->name . ' 编辑，请稍后再试。'
                        ]
                    ]);
            }
            // 如果是当前用户锁定的，刷新锁定时间
            else {
                $page->refreshLock();
            }
        } else {
            // 尝试锁定页面
            $page->lock(auth()->user());
        }
        
        // 获取当前用户的草稿或当前版本
        $draft = WikiPageDraft::where('wiki_page_id', $page->id)
            ->where('user_id', auth()->id())
            ->first();
        
        $content = $draft ? $draft->content : $page->currentVersion->content;
        
        // 加载页面相关数据
        $page->load(['currentVersion', 'categories', 'tags']);
        $categories = WikiCategory::all();
        $tags = WikiTag::all();
        
        return Inertia::render('Wiki/Edit', [
            'page' => $page,
            'content' => $content,
            'categories' => $categories,
            'tags' => $tags,
            'hasDraft' => !is_null($draft),
            'lockInfo' => [
                'isLocked' => $page->isLocked(),
                'lockedBy' => $page->isLocked() ? [
                    'id' => $page->locker->id,
                    'name' => $page->locker->name
                ] : null,
                'lockedUntil' => $page->locked_until ? $page->locked_until->format('Y-m-d H:i:s') : null
            ]
        ]);
    }

    /**
     * 刷新页面锁定时间
     *
     * @param Request $request 请求对象
     * @return \Illuminate\Http\JsonResponse
     */
    public function refreshLock(Request $request)
    {
        
        $validated = $request->validate([
            'page_id' => 'required|exists:wiki_pages,id'
        ]);
        
        $page = WikiPage::findOrFail($validated['page_id']);
        
        // 只有当前锁定者可以刷新锁
        if ($page->isLocked() && $page->locked_by === auth()->id()) {
            $page->refreshLock();
            
            return response()->json([
                'message' => '锁定时间已刷新',
                'locked_until' => $page->locked_until->format('Y-m-d H:i:s'),
                'success' => true
            ]);
        }
        
        return response()->json([
            'message' => '无法刷新锁定时间',
            'success' => false
        ], 403);
    }
    
    // 保存页面草稿
    public function saveDraft(Request $request, WikiPage $page)
    {
        
        $validated = $request->validate([
            'content' => 'required|string'
        ]);
        
        // 检查页面是否被锁定
        if ($page->isLocked() && $page->locked_by !== auth()->id()) {
            return response()->json([
                'message' => '该页面已被其他用户锁定，无法保存草稿'
            ], 403);
        }
        
        // 更新或创建草稿
        $draft = WikiPageDraft::updateOrCreate(
            [
                'wiki_page_id' => $page->id,
                'user_id' => auth()->id()
            ],
            [
                'content' => $validated['content'],
                'last_saved_at' => now()
            ]
        );
        
        return response()->json([
            'message' => '草稿已保存',
            'saved_at' => now()->format('Y-m-d H:i:s'),
            'draft_id' => $draft->id
        ]);
    }
    public function getPageTree()
    {
        $pages = WikiPage::where('status', WikiPage::STATUS_PUBLISHED)
            ->select(['id', 'title', 'slug', 'parent_id', 'order'])
            ->orderBy('order')
            ->get();
            
        return response()->json([
            'pages' => $pages
        ]);
    }
    // 更新页面

    public function update(Request $request, WikiPage $page)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category_ids' => 'required|array',
            'category_ids.*' => 'exists:wiki_categories,id',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:wiki_tags,id',
            'comment' => 'nullable|string|max:255'
        ]);
        
        $validated['content'] = Purifier::clean($validated['content']);
        
        // 锁定检查
        if ($page->isLocked() && $page->locked_by !== auth()->id()) {
            throw ValidationException::withMessages([
                'general' => ['该页面正在被其他用户编辑，请稍后再试。']
            ]);
        }
        
        // 冲突检测
        $currentVersion = $page->currentVersion;
        $lastEditVersionId = $request->version_id;
        
        // 如果用户编辑的不是最新版本，需要检查冲突
        if ($currentVersion->id !== $lastEditVersionId) {
            $diffService = app(DiffService::class);
            $lastCommonVersion = WikiVersion::find($lastEditVersionId);
            
            if ($lastCommonVersion && $diffService->hasConflict(
                $lastCommonVersion->content,
                $currentVersion->content,
                $validated['content']
            )) {
                // 标记页面为冲突状态
                $page->markAsConflict();
                
                // 保存冲突版本，但不设为当前版本
                $latestVersion = $page->versions()->latest('version_number')->first();
                $newVersionNumber = $latestVersion->version_number + 1;
                
                WikiVersion::create([
                    'wiki_page_id' => $page->id,
                    'content' => $validated['content'],
                    'created_by' => auth()->id(),
                    'version_number' => $newVersionNumber,
                    'comment' => $validated['comment'] ?? '冲突版本',
                    'is_current' => false,
                ]);
                
                // 记录冲突日志
                ActivityLog::log('conflict_detected', $page, [
                    'user_id' => auth()->id(),
                    'current_version' => $currentVersion->version_number,
                    'edit_from_version' => $lastCommonVersion->version_number
                ]);
                
                return redirect()->route('wiki.show', $page->slug)
                    ->with('flash', [
                        'message' => [
                            'type' => 'error',
                            'text' => '编辑冲突：该页面已被其他用户修改，需要管理员解决冲突。'
                        ]
                    ]);
            }
        }
        
        // 没有冲突，正常更新页面
        try {
            DB::beginTransaction();
            
            // 更新页面标题
            $page->update([
                'title' => $validated['title']
            ]);
            
            // 创建新版本
            $latestVersion = $page->versions()->latest('version_number')->first();
            $newVersionNumber = $latestVersion->version_number + 1;
            
            $version = WikiVersion::create([
                'wiki_page_id' => $page->id,
                'content' => $validated['content'],
                'created_by' => auth()->id(),
                'version_number' => $newVersionNumber,
                'comment' => $validated['comment'] ?? '更新内容',
                'is_current' => true,
            ]);
            
            // 将其他版本标记为非当前版本
            WikiVersion::where('wiki_page_id', $page->id)
                ->where('id', '!=', $version->id)
                ->update(['is_current' => false]);
            
            // 更新页面当前版本
            $page->update([
                'current_version_id' => $version->id
            ]);
            
            // 更新分类和标签
            $page->categories()->sync($validated['category_ids']);
            $page->tags()->sync($validated['tag_ids'] ?? []);
            
            // 删除草稿
            WikiPageDraft::where('wiki_page_id', $page->id)
                ->where('user_id', auth()->id())
                ->delete();
            
            // 解锁页面
            $page->unlock();
            
            DB::commit();
            
            return redirect()->route('wiki.show', $page->slug)
                ->with('flash', [
                    'message' => [
                        'type' => 'success',
                        'text' => '页面更新成功！'
                    ]
                ]);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    // 解锁页面
    public function unlock(Request $request)
    {
        
        $validated = $request->validate([
            'page_id' => 'required|exists:wiki_pages,id'
        ]);
        
        $page = WikiPage::findOrFail($validated['page_id']);
        
        // 只有锁定者或管理员才能解锁
        if ($page->locked_by === auth()->id() || auth()->user()->hasPermission('wiki.resolve_conflict')) {
            $page->unlock();
            
            return response()->json([
                'message' => '页面已解锁',
                'success' => true
            ]);
        }
        
        return response()->json([
            'message' => '您无权解锁此页面',
            'success' => false
        ], 403);
    }
    
    // 删除页面
    public function destroy(WikiPage $page)
    {
        
        // 软删除页面
        $page->delete();
        
        return redirect()->route('wiki.index')
            ->with('flash', [
                'message' => [
                    'type' => 'success',
                    'text' => '页面已删除！'
                ]
            ]);
    }
    
    // 查看历史版本
    public function showVersion(WikiPage $page, int $version)
    {
        $versionRecord = WikiVersion::where('wiki_page_id', $page->id)
            ->where('version_number', $version)
            ->firstOrFail();
            
        return Inertia::render('Wiki/ShowVersion', [
            'page' => $page->load(['creator', 'categories', 'tags']),
            'version' => $versionRecord,
            'versionCreator' => $versionRecord->creator
        ]);
    }
    
    // 查看版本历史
    public function history(WikiPage $page)
    {
        $versions = WikiVersion::where('wiki_page_id', $page->id)
            ->with('creator')
            ->orderBy('version_number', 'desc')
            ->paginate(15);
            
        return Inertia::render('Wiki/History', [
            'page' => $page,
            'versions' => $versions
        ]);
    }
    
    // 比较版本
    public function compareVersions(WikiPage $page, int $fromVersion, int $toVersion)
    {
        $from = WikiVersion::where('wiki_page_id', $page->id)
            ->where('version_number', $fromVersion)
            ->firstOrFail();
            
        $to = WikiVersion::where('wiki_page_id', $page->id)
            ->where('version_number', $toVersion)
            ->firstOrFail();
            
        return Inertia::render('Wiki/Compare', [
            'page' => $page,
            'fromVersion' => $from,
            'toVersion' => $to,
            'fromCreator' => $from->creator,
            'toCreator' => $to->creator
        ]);
    }
    
    // 恢复到指定版本
    public function revertToVersion(Request $request, WikiPage $page, int $version)
    {
        
        $versionRecord = WikiVersion::where('wiki_page_id', $page->id)
            ->where('version_number', $version)
            ->firstOrFail();
            
        try {
            DB::beginTransaction();
            
            // 创建新版本（基于旧版本的内容）
            $latestVersion = $page->versions()->latest('version_number')->first();
            $newVersionNumber = $latestVersion->version_number + 1;
            
            $newVersion = WikiVersion::create([
                'wiki_page_id' => $page->id,
                'content' => $versionRecord->content,
                'created_by' => auth()->id(),
                'version_number' => $newVersionNumber,
                'comment' => '恢复自版本 ' . $version,
                'is_current' => true,
            ]);
            
            // 更新所有其他版本为非当前版本
            WikiVersion::where('wiki_page_id', $page->id)
                ->where('id', '!=', $newVersion->id)
                ->update(['is_current' => false]);
            
            // 更新页面的当前版本
            $page->update([
                'current_version_id' => $newVersion->id
            ]);
            
            DB::commit();
            
            return redirect()->route('wiki.show', $page->slug)
                ->with('flash', [
                    'message' => [
                        'type' => 'success',
                        'text' => '页面已恢复到版本 ' . $version
                    ]
                ]);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    // 解决冲突
    public function resolveConflict(Request $request, WikiPage $page)
    {
        
        $validated = $request->validate([
            'content' => 'required|string',
            'resolution_comment' => 'nullable|string|max:255'
        ]);
        
        try {
            DB::beginTransaction();
            
            // 创建新版本（解决冲突后的内容）
            $latestVersion = $page->versions()->latest('version_number')->first();
            $newVersionNumber = $latestVersion->version_number + 1;
            
            $newVersion = WikiVersion::create([
                'wiki_page_id' => $page->id,
                'content' => $validated['content'],
                'created_by' => auth()->id(),
                'version_number' => $newVersionNumber,
                'comment' => $validated['resolution_comment'] ?? '解决编辑冲突',
                'is_current' => true,
            ]);
            
            // 更新所有其他版本为非当前版本
            WikiVersion::where('wiki_page_id', $page->id)
                ->where('id', '!=', $newVersion->id)
                ->update(['is_current' => false]);
            
            // 更新页面状态和当前版本
            $page->update([
                'current_version_id' => $newVersion->id,
                'status' => WikiPage::STATUS_PUBLISHED
            ]);
            
            // 解锁页面
            $page->resolveConflict();
            
            // 记录活动日志
            ActivityLog::log('resolve_conflict', $page, [
                'resolved_by' => auth()->id(),
                'comment' => $validated['resolution_comment'] ?? null
            ]);
            
            DB::commit();
            
            return redirect()->route('wiki.show', $page->slug)
                ->with('flash', [
                    'message' => [
                        'type' => 'success',
                        'text' => '冲突已解决！'
                    ]
                ]);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function showConflicts(WikiPage $page)
    {
        
        if ($page->status !== WikiPage::STATUS_CONFLICT) {
            return redirect()->route('wiki.show', $page->slug)
                ->with('flash', [
                    'message' => [
                        'type' => 'info',
                        'text' => '该页面当前没有冲突需要解决。'
                    ]
                ]);
        }
        
        // 获取当前版本
        $currentVersion = $page->currentVersion;
        
        // 获取最新的非当前版本（冲突版本）
        $conflictVersion = $page->versions()
            ->where('is_current', false)
            ->latest('version_number')
            ->with('creator')
            ->first();
        
        if (!$conflictVersion) {
            // 如果没有冲突版本，可能是误标记，修正状态
            $page->update(['status' => WikiPage::STATUS_PUBLISHED]);
            
            return redirect()->route('wiki.show', $page->slug)
                ->with('flash', [
                    'message' => [
                        'type' => 'info',
                        'text' => '没有发现冲突版本，页面状态已恢复。'
                    ]
                ]);
        }
        
        // 使用DiffService生成差异HTML用于前端展示
        $diffService = new \App\Services\DiffService();
        
        return Inertia::render('Wiki/ShowConflicts', [
            'page' => $page,
            'conflictVersions' => [
                'current' => array_merge($currentVersion->toArray(), [
                    'creator' => $currentVersion->creator
                ]),
                'conflict' => array_merge($conflictVersion->toArray(), [
                    'creator' => $conflictVersion->creator
                ])
            ],
            'diffHtml' => $diffService->generateDiffHtml($currentVersion->content, $conflictVersion->content)
        ]);
    }

    /**
     * 获取当前页面编辑者列表
     *
     * @param WikiPage $page 页面对象
     * @return \Illuminate\Http\JsonResponse
     */
    public function getEditors(WikiPage $page)
    {
        $collaborationService = app(CollaborationService::class);
        $editors = $collaborationService->getEditors($page->id);
        
        return response()->json([
            'editors' => array_values($editors)
        ]);
    }

    /**
     * 注册当前用户为页面编辑者
     *
     * @param Request $request 请求对象
     * @param WikiPage $page 页面对象
     * @return \Illuminate\Http\JsonResponse
     */
    public function registerEditor(Request $request, WikiPage $page)
    {
        $collaborationService = app(CollaborationService::class);
        $collaborationService->registerEditor($page, auth()->user());
        
        return response()->json([
            'success' => true,
            'message' => '已注册为页面编辑者'
        ]);
    }

    /**
     * 注销当前用户的编辑状态
     *
     * @param Request $request 请求对象
     * @param WikiPage $page 页面对象
     * @return \Illuminate\Http\JsonResponse
     */
    public function unregisterEditor(Request $request, WikiPage $page)
    {
        $collaborationService = app(CollaborationService::class);
        $collaborationService->unregisterEditor($page, auth()->user());
        
        return response()->json([
            'success' => true,
            'message' => '已注销编辑状态'
        ]);
    }

    /**
     * 获取页面讨论消息
     *
     * @param WikiPage $page 页面对象
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDiscussionMessages(WikiPage $page)
    {
        $collaborationService = app(CollaborationService::class);
        $messages = $collaborationService->getDiscussionMessages($page->id);
        
        return response()->json([
            'messages' => $messages
        ]);
    }

    /**
     * 发送讨论消息
     *
     * @param Request $request 请求对象
     * @param WikiPage $page 页面对象
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendDiscussionMessage(Request $request, WikiPage $page)
    {
        
        $validated = $request->validate([
            'message' => 'required|string|max:500'
        ]);
        
        $collaborationService = app(CollaborationService::class);
        $message = $collaborationService->addDiscussionMessage(
            $page, 
            auth()->user(),
            $validated['message']
        );
        
        return response()->json([
            'success' => true,
            'message' => $message
        ]);
    }
}
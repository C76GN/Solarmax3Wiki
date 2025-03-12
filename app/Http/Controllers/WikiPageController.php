<?php
// FileName: /var/www/Solarmax3Wiki/app/Http/Controllers/WikiPageController.php


namespace App\Http\Controllers;

use App\Models\WikiCategory;
use App\Models\WikiPage;
use App\Models\WikiPageIssue;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use function Pest\Laravel\json;

class WikiPageController extends Controller
{

    public function audit(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:wiki_pages,id',
            'status' => 'required|string|in:published,draft,pending,audit_failure',
            'status_message' => 'nullable|string|max:255'
        ]);

        WikiPage::where('id', $validated['id'])->update([
            'status' => $validated['status'],
            'status_message' => $validated['status_message'] ?? "",
        ]);

        return redirect()->route('wiki.index')->with('flash', [
            'message' => ['type' => 'success', 'text' => '审核状态更新成功']
        ]);
    }



    public function issue(Request $request)
    {
        $validated = $request->validate(['page_id' => 'required', 'content' => 'required|string',]);

        WikiPageIssue::create(['wiki_page_id' => $validated['page_id'], 'reported_by' => auth()->id(), 'content' => $validated['content'], 'status' => 'to_be_solved',]);
        return redirect()->route('wiki.show', ['page' => $validated['page_id']])->with('flash', ['message' => ['type' => 'success', 'text' => '提交成功']]);
    }

    public function issue_handle(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:wiki_page_issues,id',
        ]);

        $issue = WikiPageIssue::find($validated['id']);

        $issue->update(['status' => WikiPageIssue::STATUS_HANDLED]);

        return redirect()->route('wiki.show', ['page' => $issue->wiki_page_id])->with('flash', [
            'message' => ['type' => 'success', 'text' => '提交成功']
        ]);
    }


    public function create()
    {
        if (!auth()->user()->hasPermission('wiki.create')) {
            return $this->unauthorized();
        }

        $categories = WikiCategory::orderBy('order')->get()->map(fn($category) => ['id' => $category->id, 'name' => $category->name, 'description' => $category->description]);

        return Inertia::render('Wiki/Create', ['categories' => $categories]);
    }

    public function index(Request $request)
    {
        $query = WikiPage::with(['creator', 'lastEditor', 'categories']);


        // 搜索条件
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                ->orWhere('content', 'like', '%' . $request->search . '%');
            });
        }

        // 状态筛选
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // 分类筛选
        if ($request->has('category') && $request->category) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('wiki_categories.id', $request->category);
            });
        }

        $uid = $request->user()?->id ?: 0;
        $wikiAudit = $request->user()?->hasPermission('wiki.page_audit');
        $wikiAuditWhere = "";
        if ($wikiAudit) {
            $wikiAuditWhere = " OR (status = 'pending')";
        }

        // 📌 **新增排序逻辑**
        if ($request->has('sort')) {
            if ($request->sort === 'created_at') {
                $query->orderBy('created_at', 'desc');
            } elseif ($request->sort === 'updated_at') {
                $query->orderBy('updated_at', 'desc');
            } elseif ($request->sort === 'view_count') {
                $query->orderBy('view_count', 'desc');
            }
        } else {
            // 默认按浏览量排序
            $query->orderBy('view_count', 'desc');
        }

        $pages = $query
            ->whereRaw(
                "( ((status='draft' or status = 'audit_failure') and created_by = ?) or (status = 'published') $wikiAuditWhere )",
                [$uid]
            )
            ->paginate(10)
            ->through(fn($page) => [
                'id' => $page->id,
                'title' => $page->title,
                'status_message' => $page->status_message,
                'status' => $page->status,
                'created_by' => $page->created_by,
                'creator' => $page->creator ? ['name' => $page->creator->name] : null,
                'lastEditor' => $page->lastEditor ? ['name' => $page->lastEditor->name] : null,
                'categories' => $page->categories->map(fn($category) => ['id' => $category->id, 'name' => $category->name]),
                'published_at' => optional($page->published_at)->toDateTimeString(),
                'view_count' => $page->view_count,
                'created_at' => optional($page->created_at)->toDateTimeString(),
            ]);

        // 获取所有分类用于筛选
        $categories = WikiCategory::orderBy('order')->withCount(["pages"])->get()->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'pages_count' => $category->pages_count,
            ];
        });

        return Inertia::render('Wiki/Index', [
            'pages' => $pages,
            'categories' => $categories,
            'uid' => $uid,
            'filters' => $request->only(['search', 'status', 'category', 'sort']),
            'can' => [
                'create_page' => $request->user()?->hasPermission('wiki.create'),
                'edit_page' => $request->user()?->hasPermission('wiki.edit'),
                'delete_page' => $request->user()?->hasPermission('wiki.delete'),
                'show_status' => $request->user()?->hasPermission('wiki.status_show'),
                'audit_page' => $wikiAudit,
            ],
        ]);
    }

    private function validateRequest(Request $request, $pageId = null)
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255', Rule::unique('wiki_pages')->ignore($pageId)],
            'content' => 'required|string',
            'categories' => 'nullable|array',
            'categories.*' => 'nullable|exists:wiki_categories,id'
        ]);
    }


    private function generateUniqueSlug($title)
    {
        $baseSlug = Str::slug($title);
        $slug = $baseSlug;
        $counter = 1;

        while (WikiPage::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }


    public function store(Request $request)
    {
        $validated = $this->validateRequest($request);

        $slug = $this->generateUniqueSlug($validated['title']);

        $page = WikiPage::create([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'slug' => $slug,
            'created_by' => auth()->id(),
            'last_edited_by' => auth()->id(),
        ]);

        if (!empty($validated['categories'])) {
            $page->categories()->sync($validated['categories']);
        }

        return redirect()->route('wiki.index')->with('flash', [
            'message' => ['type' => 'success', 'text' => '页面创建成功！']
        ]);
    }

    /**
     * 获取页面当前状态，包括是否有人正在编辑和是否有更新
     */
    public function getPageStatus(WikiPage $page, Request $request)
    {
        // 确保用户已认证
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $lastCheck = $request->query('last_check');
        
        // 检查页面是否已被修改
        $hasBeenModified = false;
        if ($lastCheck) {
            $hasBeenModified = $page->updated_at->greaterThan($lastCheck);
        }
        
        // 获取当前正在编辑的用户列表（除了当前用户）
        $currentEditors = Cache::get("editing_page:{$page->id}", []);
        $currentUser = auth()->id();
        
        // 过滤掉当前用户和超过10分钟未活动的用户
        $activeEditors = [];
        $usernames = [];
        
        foreach ($currentEditors as $userId => $timestamp) {
            if ($userId != $currentUser && now()->diffInMinutes($timestamp) < 10) {
                $activeEditors[$userId] = $timestamp;
                
                // 获取用户名
                $user = \App\Models\User::find($userId);
                if ($user) {
                    $usernames[] = $user->name;
                }
            }
        }
        
        return response()->json([
            'hasBeenModified' => $hasBeenModified,
            'currentEditors' => $usernames,
            'lastModified' => $page->updated_at
        ])->header('Content-Type', 'application/json');
    }

    /**
     * 通知系统用户正在编辑页面
     */
    public function notifyEditing(WikiPage $page)
    {
        $userId = auth()->id();
        
        // 获取当前编辑者列表
        $currentEditors = Cache::get("editing_page:{$page->id}", []);
        
        // 更新当前用户的时间戳
        $currentEditors[$userId] = now();
        
        // 存储更新后的列表，设置30分钟过期
        Cache::put("editing_page:{$page->id}", $currentEditors, now()->addMinutes(30));
        
        return response()->json(['success' => true]);
    }

    /**
     * 通知系统用户停止编辑页面
     */
    public function notifyStoppedEditing(WikiPage $page)
    {
        $userId = auth()->id();
        
        // 获取当前编辑者列表
        $currentEditors = Cache::get("editing_page:{$page->id}", []);
        
        // 移除当前用户
        if (isset($currentEditors[$userId])) {
            unset($currentEditors[$userId]);
        }
        
        // 更新缓存
        if (count($currentEditors) > 0) {
            Cache::put("editing_page:{$page->id}", $currentEditors, now()->addMinutes(30));
        } else {
            Cache::forget("editing_page:{$page->id}");
        }
        
        return response()->json(['success' => true]);
    }

    /**
     * 比较当前编辑中的版本与数据库中的版本
     */
    public function compareLive(WikiPage $page, Request $request)
    {
        $currentContent = $page->content;
        $editingContent = $request->input('content');
        
        // 这里可以实现差异比较逻辑，但简单起见，我们直接返回一个视图
        return Inertia::render('Wiki/CompareLive', [
            'page' => $page->only('id', 'title'),
            'databaseVersion' => [
                'content' => $currentContent,
                'updated_at' => $page->updated_at
            ],
            'editingVersion' => [
                'content' => $editingContent
            ]
        ]);
    }


    public function edit(WikiPage $page)
    {
        if (!auth()->user()->hasPermission('wiki.edit')) {
            return $this->unauthorized();
        }

        // 确保加载所有必要的关联数据
        $page->load(['categories']);

        $categories = WikiCategory::orderBy('order')->get()->map(fn($category) => [
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

    public function show(WikiPage $page, Request $request)
    {
        // 增加浏览量
        $page->incrementViewCount();

        // 获取留言查询
        $issuesQuery = WikiPageIssue::query()->where("wiki_page_id", $page->id);

        // 📌 **新增：筛选“未解决”留言**
        if ($request->has('filter') && $request->filter === 'unresolved') {
            $issuesQuery->where('status', 'to_be_solved');
        }

        // 获取留言数据
        $issues = $issuesQuery->orderBy("created_at", 'desc')->get();

        return Inertia::render('Wiki/Show', [
            'page' => array_merge(
                $page->load([
                    'creator', 'lastEditor', 'categories', 'referencedPages', 'referencedByPages'
                ])->toArray(),
                [
                    'can' => [
                        'issue' => auth()->user()?->hasPermission('wiki.issue')
                    ],
                    'issue' => $issues->toArray(), // 📌 **留言数据**
                    'related_pages' => $page->getRelatedPages(),
                    'is_following' => $page->isFollowedByUser(auth()->id()),
                    'references_count' => $page->incomingReferences()->count(),
                    'recent_revisions' => $page->revisions()->with('creator:id,name')->latest()->take(5)->get(['id', 'version', 'created_by']),
                ]
            )
        ]);
    }

    public function revisions(WikiPage $page)
    {
        $revisions = $page->revisions()->with('creator')->orderBy('version', 'desc')->paginate(20);

        return Inertia::render('Wiki/Revisions', ['page' => $page->only('id', 'title'), 'revisions' => $revisions, 'can' => ['edit' => auth()->user()?->hasPermission('wiki.edit')]]);
    }

    public function destroy(WikiPage $page)
    {
        if (!auth()->user()->hasPermission('wiki.delete')) {
            return $this->unauthorized();
        }

        DB::transaction(function () use ($page) {
            $page->outgoingReferences()->delete();
            $page->incomingReferences()->delete();
            $page->delete();
        });

        return redirect()->route('wiki.index')->with('flash', [
            'message' => ['type' => 'success', 'text' => '页面删除成功！']
        ]);
    }


    public function publish(WikiPage $page)
    {
        if (!auth()->user()->hasPermission('wiki.publish')) {
            return $this->unauthorized();
        }

        $page->update(['status' => 'published', 'published_at' => now(),]);

        return redirect()->route('wiki.index')->with('flash', ['message' => ['type' => 'success', 'text' => '页面发布成功！']]);
    }

    public function update(Request $request, WikiPage $page)
    {
        $validated = $this->validateRequest($request, $page->id);
        $validated['last_edited_by'] = auth()->id();
        $validated['status'] = 'pending';
        
        // 检查是否为强制更新
        if (!$request->has('force_update')) {
            // 检查页面是否已被他人修改
            $lastUpdated = $page->updated_at;
            if ($request->has('last_check') && $lastUpdated->greaterThan($request->input('last_check'))) {
                return response()->json([
                    'conflict' => true,
                    'message' => '页面已被他人修改，请刷新后重试或选择强制更新'
                ], 409);
            }
        }
        
        // 更新页面
        $page->update($validated);
        $page->categories()->sync($validated['categories'] ?? []);
        $page->updateReferences();
        
        // 移除当前用户的编辑状态
        $this->removeFromEditingList($page->id, auth()->id());
        
        return redirect()->route('wiki.index')->with('flash', [
            'message' => ['type' => 'success', 'text' => '页面更新成功！']
        ]);
    }

    /**
     * 从编辑列表中移除用户
     */
    private function removeFromEditingList($pageId, $userId)
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


    public function showRevision(WikiPage $page, $version)
    {
        $revision = $page->revisions()->where('version', $version)->with('creator')->firstOrFail();

        return Inertia::render('Wiki/Show', ['page' => array_merge($page->only('id', 'title', 'current_version'), ['content' => $revision->content, 'revision' => $revision]), 'can' => ['edit' => auth()->user()?->hasPermission('wiki.edit')]]);
    }

    public function compareRevisions(WikiPage $page, $fromVersion, $toVersion)
    {
        $fromRevision = $page->revisions()->where('version', $fromVersion)->with('creator')->firstOrFail();

        $toRevision = $page->revisions()->where('version', $toVersion)->with('creator')->firstOrFail();

        return Inertia::render('Wiki/CompareRevisions', ['page' => $page->only('id', 'title'), 'oldRevision' => $fromRevision, 'newRevision' => $toRevision, 'fromVersion' => (int)$fromVersion, 'toVersion' => (int)$toVersion]);
    }

    public function revertToVersion(Request $request, WikiPage $page, $version)
    {
        if (!auth()->user()->hasPermission('wiki.edit')) {
            return $this->unauthorized();
        }

        try {
            $page->revertToVersion($version);

            return redirect()->route('wiki.show', $page->id)->with('flash', ['message' => ['type' => 'success', 'text' => "页面已恢复到版本 {$version}"]]);
        } catch (Exception $e) {
            return redirect()->back()->with('flash', ['message' => ['type' => 'error', 'text' => '恢复失败：' . $e->getMessage()]]);
        }
    }

    public function toggleFollow(WikiPage $page)
    {
        $user = auth()->user();
        $isFollowing = $page->isFollowedByUser($user->id);

        $isFollowing ? $page->followers()->detach($user->id) : $page->followers()->attach($user->id);

        return response()->json([
            'followed' => !$isFollowing,
            'message' => $isFollowing ? '取消关注成功' : '关注成功'
        ]);
    }


    public function trash()
    {
        if (!auth()->user()->hasPermission('wiki.manage_trash')) {
            return $this->unauthorized();
        }

        $trashedPages = WikiPage::onlyTrashed()->with(['creator', 'lastEditor', 'categories'])->latest('deleted_at')->paginate(10)->through(fn($page) => ['id' => $page->id, 'title' => $page->title, 'creator' => $page->creator ? ['name' => $page->creator->name,] : null, 'lastEditor' => $page->lastEditor ? ['name' => $page->lastEditor->name,] : null, 'categories' => $page->categories->map(fn($category) => ['id' => $category->id, 'name' => $category->name]), 'deleted_at' => $page->deleted_at->format('Y-m-d H:i:s'),]);

        return Inertia::render('Wiki/Trash', ['pages' => $trashedPages,]);
    }

    // 恢复已删除的页面
    public function restore($id)
    {
        if (!auth()->user()->hasPermission('wiki.manage_trash')) {
            return $this->unauthorized();
        }

        $page = WikiPage::onlyTrashed()->findOrFail($id);

        try {
            DB::beginTransaction();

            // 恢复页面及其关联
            $page->restore();

            DB::commit();

            return redirect()->back()->with('flash', ['message' => ['type' => 'success', 'text' => '页面已成功恢复！']]);
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('flash', ['message' => ['type' => 'error', 'text' => '恢复失败：' . $e->getMessage()]]);
        }
    }

    // 彻底删除页面
    public function forceDelete($id)
    {
        if (!auth()->user()->hasPermission('wiki.manage_trash')) {
            return $this->unauthorized();
        }

        $page = WikiPage::onlyTrashed()->findOrFail($id);

        try {
            DB::beginTransaction();

            // 删除所有相关引用
            $page->outgoingReferences()->forceDelete();
            $page->incomingReferences()->forceDelete();

            // 彻底删除页面
            $page->forceDelete();

            DB::commit();

            return redirect()->back()->with('flash', ['message' => ['type' => 'success', 'text' => '页面已彻底删除！']]);
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('flash', ['message' => ['type' => 'error', 'text' => '删除失败：' . $e->getMessage()]]);
        }
    }
}

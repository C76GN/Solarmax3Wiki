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
        $validated = $request->validate(['id' => 'required', 'status' => 'required|string','status_message'=>'string']);
        WikiPage::query()->where("id",$validated['id'])->update([
            'status'=>$validated['status'],
            'status_message'=>$validated['status_message']??"",
        ]);
        return redirect()->route('wiki.index')->with('flash', ['message' => ['type' => 'success', 'text' => '提交成功']]);
    }


    public function issue(Request $request)
    {
        $validated = $request->validate(['page_id' => 'required', 'content' => 'required|string',]);

        WikiPageIssue::create(['wiki_page_id' => $validated['page_id'], 'reported_by' => auth()->id(), 'content' => $validated['content'], 'status' => 'to_be_solved',]);
        return redirect()->route('wiki.show', ['page' => $validated['page_id']])->with('flash', ['message' => ['type' => 'success', 'text' => '提交成功']]);
    }

    public function issue_handle(Request $request)
    {
        $validated = $request->validate(['id' => 'required']);
        $w = WikiPageIssue::query()->find($validated['id']);

        WikiPageIssue::query()->where("id", $validated['id'])->update([
            'status' => 'handle'
        ]);
        return redirect()->route('wiki.show', ['page' => $w['wiki_page_id']])->with('flash', ['message' => ['type' => 'success', 'text' => '提交成功']]);
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

        if ($request->has('prev_page_id') && $request->prev_page_id){
            Cache::delete("editing_page:id:$request->prev_page_id");
        }

        // 搜索条件
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')->orWhere('content', 'like', '%' . $request->search . '%');
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

        $uid = $request->user()?->id?:0;
        $wikiAudit = $request->user()?->hasPermission('wiki.page_audit');
        $wikiAuditWhere = "";
        if ($wikiAudit){
            $wikiAuditWhere = " or (status = 'pending')";
        }
        //wiki.page_audit

        $pages = $query->orderBy("view_count",'desc')
            ->whereRaw("( ((status='draft' or status = 'audit_failure') and created_by = $uid) or (status = 'published') $wikiAuditWhere )")
            ->paginate(10)
            ->through(fn($page) => [
                'id' => $page->id,
                'title' => $page->title,
                'status_message' => $page->status_message,
                'status' => $page->status,
                'created_by' => $page->created_by,
                'creator' => $page->creator ? ['name' => $page->creator->name] : null,
                'lastEditor' => $page->lastEditor ? ['name' => $page->lastEditor->name,] : null,
                'categories' => $page->categories->map(fn($category) => ['id' => $category->id, 'name' => $category->name]), 'published_at' => $page->published_at, 'view_count' => $page->view_count, 'created_at' => $page->created_at,]);

        // 获取所有分类用于筛选
        $categories = WikiCategory::orderBy('order')->withCount(["pages"])->get()->map(function ($category) {
            return ['id' => $category->id, 'name' => $category->name, 'pages_count' => $category->pages_count,];
        });


//        dump($categories->toArray());die;
        return Inertia::render('Wiki/Index', [
            'pages' => $pages,
            'categories' => $categories,
            'uid'=>$uid,
            'filters' => $request->only(['search', 'status', 'category']),
            'can' => [
                'create_page' => $request->user()?->hasPermission('wiki.create'),
                'edit_page' => $request->user()?->hasPermission('wiki.edit'),
                'delete_page' => $request->user()?->hasPermission('wiki.delete'),
                'show_status' => $request->user()?->hasPermission('wiki.status_show'),
                'audit_page' => $wikiAudit,
                ],
            ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate(['title' => 'required|string|max:255|unique:wiki_pages', 'content' => 'required|string', 'categories' => 'array', 'categories.*' => 'exists:wiki_categories,id']);

        $baseSlug = Str::slug($validated['title']);
        $slug = $baseSlug;
        $counter = 1;

        while (WikiPage::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        $page = WikiPage::create(['title' => $validated['title'], 'content' => $validated['content'], 'slug' => $slug, 'created_by' => auth()->id(), 'last_edited_by' => auth()->id(),]);

        if (!empty($validated['categories'])) {
            $page->categories()->sync($validated['categories']);
        }
        $page->updateReferences();

        return redirect()->route('wiki.index')->with('flash', ['message' => ['type' => 'success', 'text' => '页面创建成功！']]);
    }


    public function lock(Request $request)
    {
        Cache::put("editing_page:id:$request->page_id",session()->getId(),10);
        return response()->json([
            'message' => 'locking',
        ], 200)->header('X-Inertia', true);
    }

    public function edit(WikiPage $page)
    {
        if (!auth()->user()->hasPermission('wiki.edit')) {
            return $this->unauthorized();
        }

        $cacheSessionId = cache("editing_page:id:$page->id");
//        var_dump($cacheSessionId,session()->getId() , $cacheSessionId);
        if ($cacheSessionId && session()->getId() !== $cacheSessionId){
            return $this->unauthorized("该页面正在编辑中，请稍后再试！");
        }

        //上锁
        Cache::set("editing_page:id:$page->id",session()->getId(),10);

        // 确保加载所有必要的关联数据
        $page->load(['categories']);

        $categories = WikiCategory::orderBy('order')->get()->map(fn($category) => ['id' => $category->id, 'name' => $category->name, 'description' => $category->description]);

        return Inertia::render('Wiki/Edit', ['page' => array_merge($page->toArray(), ['categories' => $page->categories->pluck('id')->toArray()]), 'categories' => $categories, 'canEdit' => true]);
    }

    public function show(WikiPage $page)
    {
        $page->incrementViewCount();

//        dump(WikiPageIssue::query()->where("wiki_page_id", $page['id'])->get()->toArray());die;

        return Inertia::render('Wiki/Show', [
            'page' => array_merge(
                $page->load(['creator', 'lastEditor', 'categories', 'referencedPages', 'referencedByPages'])->toArray(),
                [
                    'can'=>['issue' => auth()->user()?->hasPermission('wiki.issue')],
                    'issue' => WikiPageIssue::query()->where("wiki_page_id", $page['id'])->orderBy("created_at", 'desc')->get()->toArray(),
                    'related_pages' => $page->getRelatedPages(),
                    'is_following' => $page->isFollowedByUser(auth()->id()),
                    'references_count' => $page->incomingReferences()->count(),
                    'recent_revisions' => $page->revisions()->with('creator')->latest()->take(5)->get()
                ])
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

        try {
            // 开始事务
            DB::beginTransaction();

            // 删除相关的引用
            $page->outgoingReferences()->delete();
            $page->incomingReferences()->delete();

            // 删除页面
            $page->delete();

            // 提交事务
            DB::commit();

            return redirect()->route('wiki.index')->with('flash', ['message' => ['type' => 'success', 'text' => '页面删除成功！']]);
        } catch (Exception $e) {
            // 回滚事务
            DB::rollback();

            return redirect()->back()->with('flash', ['message' => ['type' => 'error', 'text' => '删除失败：' . $e->getMessage()]]);
        }
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
        $validated = $request->validate(['title' => ['required', 'string', 'max:255', Rule::unique('wiki_pages')->ignore($page->id)], 'content' => 'required|string', 'categories' => 'array', 'categories.*' => 'exists:wiki_categories,id']);

        $validated['last_edited_by'] = auth()->id();
        $validated['status'] = 'pending';
        $page->update($validated);
        $page->categories()->sync($validated['categories'] ?? []);
        $page->updateReferences();

        Cache::delete("editing_page:id:$page->id");


        return redirect()->route('wiki.index')->with('flash', ['message' => ['type' => 'success', 'text' => '页面更新成功！']]);
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

        if ($page->isFollowedByUser($user->id)) {
            $page->followers()->detach($user->id);
            $message = '取消关注成功';
        } else {
            $page->followers()->attach($user->id);
            $message = '关注成功';
        }

        return response()->json(['followed' => !$page->isFollowedByUser($user->id), 'message' => $message]);
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

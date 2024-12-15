<?php

namespace App\Http\Controllers;

use App\Models\WikiPage;
use App\Models\WikiCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class WikiPageController extends Controller
{
    public function index(Request $request)
    {
        $query = WikiPage::with(['creator', 'lastEditor', 'categories'])
            ->latest();

        // 搜索条件
        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', '%'.$request->search.'%')
                  ->orWhere('content', 'like', '%'.$request->search.'%');
            });
        }

        // 状态筛选
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // 分类筛选
        if ($request->has('category') && $request->category !== '') {
            $query->whereHas('categories', function($q) use ($request) {
                $q->where('wiki_categories.id', $request->category);
            });
        }

        $pages = $query->paginate(10)
            ->through(fn ($page) => [
                'id' => $page->id,
                'title' => $page->title,
                'status' => $page->status,
                'creator' => $page->creator ? [
                    'name' => $page->creator->name,
                ] : null,
                'lastEditor' => $page->lastEditor ? [
                    'name' => $page->lastEditor->name,
                ] : null,
                'categories' => $page->categories->map(fn($category) => [
                    'id' => $category->id,
                    'name' => $category->name
                ]),
                'published_at' => $page->published_at,
                'view_count' => $page->view_count,
                'created_at' => $page->created_at,
            ]);

        // 获取所有分类用于筛选
        $categories = WikiCategory::orderBy('order')->get()
            ->map(fn($category) => [
                'id' => $category->id,
                'name' => $category->name
            ]);

        return Inertia::render('Wiki/Index', [
            'pages' => $pages,
            'categories' => $categories,
            'filters' => $request->only(['search', 'status', 'category']),
            'can' => [
                'create_page' => $request->user()?->hasPermission('wiki.create'),
                'edit_page' => $request->user()?->hasPermission('wiki.edit'),
                'delete_page' => $request->user()?->hasPermission('wiki.delete'),
            ],
        ]);
    }

    public function create()
    {
        if (!auth()->user()->hasPermission('wiki.create')) {
            return $this->unauthorized();
        }

        $categories = WikiCategory::orderBy('order')->get()
            ->map(fn($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'description' => $category->description
            ]);

        return Inertia::render('Wiki/Create', [
            'categories' => $categories
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255|unique:wiki_pages',
            'content' => 'required|string',
            'categories' => 'array',
            'categories.*' => 'exists:wiki_categories,id'
        ]);

        $baseSlug = Str::slug($validated['title']);
        $slug = $baseSlug;
        $counter = 1;
        
        while (WikiPage::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

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
        $page->updateReferences();

        return redirect()->route('wiki.index')
            ->with('flash', [
                'message' => [
                    'type' => 'success',
                    'text' => '页面创建成功！'
                ]
            ]);
    }

    public function edit(WikiPage $page)
    {
        if (!auth()->user()->hasPermission('wiki.edit')) {
            return $this->unauthorized();
        }

        $categories = WikiCategory::orderBy('order')->get()
            ->map(fn($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'description' => $category->description
            ]);

        return Inertia::render('Wiki/Edit', [
            'page' => array_merge($page->toArray(), [
                'categories' => $page->categories->pluck('id')->toArray()
            ]),
            'categories' => $categories
        ]);
    }

    public function update(Request $request, WikiPage $page)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255', Rule::unique('wiki_pages')->ignore($page->id)],
            'content' => 'required|string',
            'categories' => 'array',
            'categories.*' => 'exists:wiki_categories,id'
        ]);

        $validated['last_edited_by'] = auth()->id();

        $page->update($validated);
        $page->categories()->sync($validated['categories'] ?? []);
        $page->updateReferences();
        return redirect()->route('wiki.index')
            ->with('flash', [
                'message' => [
                    'type' => 'success',
                    'text' => '页面更新成功！'
                ]
            ]);
    }

    public function show(WikiPage $page)
    {
        $page->incrementViewCount();
        
        return Inertia::render('Wiki/Show', [
            'page' => array_merge($page->load([
                'creator',
                'lastEditor',
                'categories',
                'referencedPages',
                'referencedByPages'
            ])->toArray(), [
                'related_pages' => $page->getRelatedPages(),
                'is_following' => $page->isFollowedByUser(auth()->id()),
                'references_count' => $page->incomingReferences()->count(),
                'recent_revisions' => $page->revisions()
                    ->with('creator')
                    ->latest()
                    ->take(5)
                    ->get()
            ])
        ]);
    }

    public function destroy(WikiPage $page)
    {
        if (!auth()->user()->hasPermission('wiki.delete')) {
            return $this->unauthorized();
        }

        $page->delete();

        return redirect()->route('wiki.index')
            ->with('flash', [
                'message' => [
                    'type' => 'success',
                    'text' => '页面删除成功！'
                ]
            ]);
    }

    public function publish(WikiPage $page)
    {
        if (!auth()->user()->hasPermission('wiki.publish')) {
            return $this->unauthorized();
        }

        $page->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        return redirect()->route('wiki.index')
            ->with('flash', [
                'message' => [
                    'type' => 'success',
                    'text' => '页面发布成功！'
                ]
            ]);
    }

    public function revisions(WikiPage $page)
    {
        $revisions = $page->revisions()
            ->with('creator')
            ->orderBy('version', 'desc')
            ->paginate(20);

        return Inertia::render('Wiki/Revisions', [
            'page' => $page,
            'revisions' => $revisions
        ]);
    }

    public function showRevision(WikiPage $page, $version)
    {
        $revision = $page->revisions()
            ->where('version', $version)
            ->with('creator')
            ->firstOrFail();

        return Inertia::render('Wiki/ShowRevision', [
            'page' => $page,
            'revision' => $revision
        ]);
    }

    public function compareRevisions(WikiPage $page, $fromVersion, $toVersion)
    {
        $diff = $page->getRevisionDiff($fromVersion, $toVersion);

        return Inertia::render('Wiki/CompareRevisions', [
            'page' => $page,
            'diff' => $diff
        ]);
    }

    public function revertToVersion(Request $request, WikiPage $page, $version)
    {
        $page->revertToVersion($version);

        return redirect()->route('wiki.show', $page->id)
            ->with('flash', [
                'message' => [
                    'type' => 'success',
                    'text' => "页面已恢复到版本 {$version}"
                ]
            ]);
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

        return response()->json([
            'followed' => !$page->isFollowedByUser($user->id),
            'message' => $message
        ]);
    }
}
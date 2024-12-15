<?php

namespace App\Http\Controllers;

use App\Models\WikiArticle;
use App\Models\WikiCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class WikiArticleController extends Controller
{
    public function index(Request $request)
    {
        $query = WikiArticle::with(['creator', 'lastEditor', 'categories'])
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

        $articles = $query->paginate(10)
            ->through(fn ($article) => [
                'id' => $article->id,
                'title' => $article->title,
                'status' => $article->status,
                'creator' => $article->creator ? [
                    'name' => $article->creator->name,
                ] : null,
                'lastEditor' => $article->lastEditor ? [
                    'name' => $article->lastEditor->name,
                ] : null,
                'categories' => $article->categories->map(fn($category) => [
                    'id' => $category->id,
                    'name' => $category->name
                ]),
                'published_at' => $article->published_at,
                'view_count' => $article->view_count,
                'created_at' => $article->created_at,
            ]);

        // 获取所有分类用于筛选
        $categories = WikiCategory::orderBy('order')->get()
            ->map(fn($category) => [
                'id' => $category->id,
                'name' => $category->name
            ]);

        return Inertia::render('Wiki/Index', [
            'articles' => $articles,
            'categories' => $categories,
            'filters' => $request->only(['search', 'status', 'category']),
            'can' => [
                'create_article' => $request->user()?->hasPermission('wiki.create'),
                'edit_article' => $request->user()?->hasPermission('wiki.edit'),
                'delete_article' => $request->user()?->hasPermission('wiki.delete'),
            ],
        ]);
    }

    public function create()
    {
        if (!auth()->user()->hasPermission('wiki.create')) {
            return $this->unauthorized();
        }

        // 获取所有分类供选择
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
            'title' => 'required|string|max:255|unique:wiki_articles',
            'content' => 'required|string',
            'categories' => 'array',
            'categories.*' => 'exists:wiki_categories,id'
        ]);

        // 生成基础 slug
        $baseSlug = Str::slug($validated['title']);
        
        // 如果 slug 已存在，添加递增数字直到找到唯一的 slug
        $slug = $baseSlug;
        $counter = 1;
        
        while (WikiArticle::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        $article = WikiArticle::create([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'slug' => $slug,
            'created_by' => auth()->id(),
            'last_edited_by' => auth()->id(),
        ]);

        // 关联分类
        if (!empty($validated['categories'])) {
            $article->categories()->sync($validated['categories']);
        }

        return redirect()->route('wiki.index')
            ->with('flash', [
                'message' => [
                    'type' => 'success',
                    'text' => '文章创建成功！'
                ]
            ]);
    }

    public function edit(WikiArticle $article)
    {
        if (!auth()->user()->hasPermission('wiki.edit')) {
            return $this->unauthorized();
        }

        // 获取所有分类供选择
        $categories = WikiCategory::orderBy('order')->get()
            ->map(fn($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'description' => $category->description
            ]);

        return Inertia::render('Wiki/Edit', [
            'article' => array_merge($article->toArray(), [
                'categories' => $article->categories->pluck('id')->toArray()
            ]),
            'categories' => $categories
        ]);
    }

    public function update(Request $request, WikiArticle $article)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255', Rule::unique('wiki_articles')->ignore($article->id)],
            'content' => 'required|string',
            'categories' => 'array',
            'categories.*' => 'exists:wiki_categories,id'
        ]);

        $validated['last_edited_by'] = auth()->id();

        $article->update($validated);

        // 更新分类关联
        $article->categories()->sync($validated['categories'] ?? []);

        return redirect()->route('wiki.index')
            ->with('flash', [
                'message' => [
                    'type' => 'success',
                    'text' => '文章更新成功！'
                ]
            ]);
    }

    public function show(WikiArticle $article)
    {
        $article->incrementViewCount();
        
        return Inertia::render('Wiki/Show', [
            'article' => array_merge($article->toArray(), [
                'creator' => $article->creator ? [
                    'name' => $article->creator->name,
                ] : null,
                'lastEditor' => $article->lastEditor ? [
                    'name' => $article->lastEditor->name,
                ] : null,
                'categories' => $article->categories->map(fn($category) => [
                    'id' => $category->id,
                    'name' => $category->name
                ])
            ])
        ]);
    }

    public function destroy(WikiArticle $article)
    {
        if (!auth()->user()->hasPermission('wiki.delete')) {
            return $this->unauthorized();
        }

        $article->delete();

        return redirect()->route('wiki.index')
            ->with('flash', [
                'message' => [
                    'type' => 'success',
                    'text' => '文章删除成功！'
                ]
            ]);
    }

    public function publish(WikiArticle $article)
    {
        if (!auth()->user()->hasPermission('wiki.publish')) {
            return $this->unauthorized();
        }

        $article->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        return redirect()->route('wiki.index')
            ->with('flash', [
                'message' => [
                    'type' => 'success',
                    'text' => '文章发布成功！'
                ]
            ]);
    }
}
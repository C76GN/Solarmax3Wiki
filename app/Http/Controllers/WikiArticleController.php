<?php

namespace App\Http\Controllers;

use App\Models\WikiArticle;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class WikiArticleController extends Controller
{
    public function index(Request $request)
    {
        $query = WikiArticle::with(['creator', 'lastEditor'])
            ->latest();

        // 搜索条件
        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', '%'.$request->search.'%')
                  ->orWhere('content', 'like', '%'.$request->search.'%');
            });
        }

        // 状态筛选
        if ($request->has('status')) {
            $query->where('status', $request->status);
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
                'published_at' => $article->published_at,
                'view_count' => $article->view_count,
                'created_at' => $article->created_at,
            ]);

        return Inertia::render('Wiki/Index', [
            'articles' => $articles,
            'filters' => $request->only(['search', 'status']),
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

        return Inertia::render('Wiki/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255|unique:wiki_articles',
            'content' => 'required|string',
        ]);

        $validated['slug'] = Str::slug($validated['title']);
        $validated['created_by'] = auth()->id();
        $validated['last_edited_by'] = auth()->id();

        WikiArticle::create($validated);

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

        return Inertia::render('Wiki/Edit', [
            'article' => $article
        ]);
    }

    public function update(Request $request, WikiArticle $article)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255', Rule::unique('wiki_articles')->ignore($article->id)],
            'content' => 'required|string',
        ]);

        $validated['last_edited_by'] = auth()->id();
        $article->update($validated);

        return redirect()->route('wiki.index')
            ->with('flash', [
                'message' => [
                    'type' => 'success',
                    'text' => '文章更新成功！'
                ]
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
            ])
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
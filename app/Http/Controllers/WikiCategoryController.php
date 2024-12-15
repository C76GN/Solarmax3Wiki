<?php

namespace App\Http\Controllers;

use App\Models\WikiCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class WikiCategoryController extends Controller
{
    public function index()
    {
        $categories = WikiCategory::with('parent')
            ->withCount('articles')
            ->orderBy('order')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                    'parent' => $category->parent ? [
                        'id' => $category->parent->id,
                        'name' => $category->parent->name
                    ] : null,
                    'articles_count' => $category->articles_count,
                    'created_at' => $category->created_at->format('Y-m-d H:i:s')
                ];
            });

        return Inertia::render('Wiki/Categories/CategoryIndex', [
            'categories' => $categories,
            'can' => [
                'create_category' => auth()->user()?->hasPermission('wiki.category.create'),
                'edit_category' => auth()->user()?->hasPermission('wiki.category.edit'),
                'delete_category' => auth()->user()?->hasPermission('wiki.category.delete'),
            ]
        ]);
    }

    public function create()
    {
        if (!auth()->user()->hasPermission('wiki.category.create')) {
            return $this->unauthorized();
        }

        $categories = WikiCategory::all()->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name
            ];
        });

        return Inertia::render('Wiki/Categories/CategoryCreate', [
            'categories' => $categories
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:wiki_categories,id',
            'order' => 'integer|min:0'
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        WikiCategory::create($validated);

        return redirect()->route('wiki.categories.index')
            ->with('flash', [
                'message' => [
                    'type' => 'success',
                    'text' => '分类创建成功！'
                ]
            ]);
    }

    public function edit(WikiCategory $category)
    {
        if (!auth()->user()->hasPermission('wiki.category.edit')) {
            return $this->unauthorized();
        }

        $categories = WikiCategory::where('id', '!=', $category->id)
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name
                ];
            });

        return Inertia::render('Wiki/Categories/CategoryEdit', [
            'category' => $category,
            'categories' => $categories
        ]);
    }


    public function update(Request $request, WikiCategory $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => [
                'nullable',
                'exists:wiki_categories,id',
                function ($attribute, $value, $fail) use ($category) {
                    if ($value == $category->id) {
                        $fail('分类不能作为自己的父分类。');
                    }
                    if ($value && $category->descendants()->pluck('id')->contains($value)) {
                        $fail('不能选择当前分类的子分类作为父分类。');
                    }
                },
            ],
            'order' => 'integer|min:0'
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $category->update($validated);

        return redirect()->route('wiki.categories.index')
            ->with('flash', [
                'message' => [
                    'type' => 'success',
                    'text' => '分类更新成功！'
                ]
            ]);
    }

    public function destroy(WikiCategory $category)
    {
        if (!auth()->user()->hasPermission('wiki.category.delete')) {
            return $this->unauthorized();
        }

        $category->delete();

        return redirect()->route('wiki.categories.index')
            ->with('flash', [
                'message' => [
                    'type' => 'success',
                    'text' => '分类删除成功！'
                ]
            ]);
    }
}
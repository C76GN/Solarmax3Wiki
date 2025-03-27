<?php

namespace App\Http\Controllers;

use App\Models\WikiCategory;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Wiki分类控制器
 * 
 * 负责管理Wiki系统中的分类，包括分类的创建、编辑、
 * 查看和删除功能
 */
class WikiCategoryController extends Controller
{
    /**
     * 显示分类列表页面
     *
     * @return Response 分类列表页面
     */
    public function index(): Response
    {
        // 获取所有分类并格式化数据
        $categories = WikiCategory::with('parent')
            ->withCount('pages')
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
                    'articles_count' => $category->pages_count,
                    'created_at' => $category->created_at->format('Y-m-d H:i:s')
                ];
            });
            
        // 获取当前用户的权限
        $currentUser = auth()->user();
        
        return Inertia::render('Wiki/Categories/CategoryIndex', [
            'categories' => $categories,
            'can' => [
                'create_category' => $currentUser?->hasPermission('wiki.category.create'),
                'edit_category' => $currentUser?->hasPermission('wiki.category.edit'),
                'delete_category' => $currentUser?->hasPermission('wiki.category.delete'),
            ]
        ]);
    }

    /**
     * 显示创建分类页面
     *
     * @return Response|RedirectResponse 创建分类页面或未授权响应
     */
    public function create(): Response|RedirectResponse
    {
        // 检查用户权限
        if (!auth()->user()->hasPermission('wiki.category.create')) {
            return $this->unauthorized();
        }
        
        // 获取所有分类作为可选的父分类
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

    /**
     * 存储新创建的分类
     *
     * @param Request $request 包含分类数据的请求
     * @return RedirectResponse 重定向到分类列表
     */
    public function store(Request $request): RedirectResponse
    {
        // 验证请求数据
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:wiki_categories,id',
            'order' => 'integer|min:0'
        ]);
        
        // 生成分类的slug
        $validated['slug'] = Str::slug($validated['name']);
        
        // 创建新分类
        WikiCategory::create($validated);
        
        // 重定向到分类列表并显示成功消息
        return redirect()->route('wiki.categories.index')
            ->with('flash', [
                'message' => [
                    'type' => 'success',
                    'text' => '分类创建成功！'
                ]
            ]);
    }

    /**
     * 显示编辑分类页面
     *
     * @param WikiCategory $category 要编辑的分类
     * @return Response|RedirectResponse 编辑分类页面或未授权响应
     */
    public function edit(WikiCategory $category): Response|RedirectResponse
    {
        // 检查用户权限
        if (!auth()->user()->hasPermission('wiki.category.edit')) {
            return $this->unauthorized();
        }
        
        // 获取除当前分类外的所有分类作为可选的父分类
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

    /**
     * 更新指定分类
     *
     * @param Request $request 包含更新数据的请求
     * @param WikiCategory $category 要更新的分类
     * @return RedirectResponse 重定向到分类列表
     */
    public function update(Request $request, WikiCategory $category): RedirectResponse
    {
        // 验证请求数据，包括防止循环引用的自定义规则
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => [
                'nullable',
                'exists:wiki_categories,id',
                function ($attribute, $value, $fail) use ($category) {
                    // 防止分类选择自己作为父分类
                    if ($value == $category->id) {
                        $fail('分类不能作为自己的父分类。');
                    }
                    // 防止选择自己的子分类作为父分类，避免循环引用
                    if ($value && $category->descendants()->pluck('id')->contains($value)) {
                        $fail('不能选择当前分类的子分类作为父分类。');
                    }
                },
            ],
            'order' => 'integer|min:0'
        ]);
        
        // 更新分类的slug
        $validated['slug'] = Str::slug($validated['name']);
        
        // 更新分类
        $category->update($validated);
        
        // 重定向到分类列表并显示成功消息
        return redirect()->route('wiki.categories.index')
            ->with('flash', [
                'message' => [
                    'type' => 'success',
                    'text' => '分类更新成功！'
                ]
            ]);
    }

    /**
     * 删除指定分类
     *
     * @param WikiCategory $category 要删除的分类
     * @return RedirectResponse 重定向到分类列表或未授权响应
     */
    public function destroy(WikiCategory $category): RedirectResponse
    {
        // 检查用户权限
        if (!auth()->user()->hasPermission('wiki.category.delete')) {
            return $this->unauthorized();
        }
        
        // 删除分类
        $category->delete();
        
        // 重定向到分类列表并显示成功消息
        return redirect()->route('wiki.categories.index')
            ->with('flash', [
                'message' => [
                    'type' => 'success',
                    'text' => '分类删除成功！'
                ]
            ]);
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\WikiCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class WikiCategoryController extends Controller
{
    // 显示分类列表
    public function index(): Response
    {
        $this->authorize('wiki.manage_categories');
        
        $categories = WikiCategory::withCount('pages')
            ->with('parent')
            ->orderBy('order')
            ->get();
            
        return Inertia::render('Wiki/Categories/Index', [
            'categories' => $categories
        ]);
    }
    
    // 显示创建分类表单
    public function create(): Response
    {
        $this->authorize('wiki.manage_categories');
        
        $categories = WikiCategory::all();
        
        return Inertia::render('Wiki/Categories/Create', [
            'categories' => $categories
        ]);
    }
    
    // 存储新分类
    public function store(Request $request)
    {
        $this->authorize('wiki.manage_categories');
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:wiki_categories,id',
            'order' => 'nullable|integer'
        ]);
        
        // 生成slug
        $slug = Str::slug($validated['name']);
        
        // 检查slug是否存在，如果存在则添加随机字符串
        if (WikiCategory::where('slug', $slug)->exists()) {
            $slug = $slug . '-' . Str::random(5);
        }
        
        WikiCategory::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'],
            'parent_id' => $validated['parent_id'],
            'order' => $validated['order'] ?? 0
        ]);
        
        return redirect()->route('wiki.categories.index')
            ->with('flash', [
                'message' => [
                    'type' => 'success',
                    'text' => '分类创建成功！'
                ]
            ]);
    }
    
    // 显示编辑分类表单
    public function edit(WikiCategory $category): Response
    {
        $this->authorize('wiki.manage_categories');
        
        $categories = WikiCategory::where('id', '!=', $category->id)->get();
        
        return Inertia::render('Wiki/Categories/Edit', [
            'category' => $category,
            'categories' => $categories
        ]);
    }
    
    // 更新分类
    public function update(Request $request, WikiCategory $category)
    {
        $this->authorize('wiki.manage_categories');
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:wiki_categories,id',
            'order' => 'nullable|integer'
        ]);
        
        // 防止分类成为自己的子分类
        if ($validated['parent_id'] && $validated['parent_id'] == $category->id) {
            return back()->withErrors(['parent_id' => '分类不能选择自己作为父分类']);
        }
        
        $category->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'parent_id' => $validated['parent_id'],
            'order' => $validated['order'] ?? 0
        ]);
        
        return redirect()->route('wiki.categories.index')
            ->with('flash', [
                'message' => [
                    'type' => 'success',
                    'text' => '分类更新成功！'
                ]
            ]);
    }
    
    // 删除分类
    public function destroy(WikiCategory $category)
    {
        $this->authorize('wiki.manage_categories');
        
        // 检查是否有子分类
        if ($category->children()->exists()) {
            return back()->withErrors(['general' => '无法删除含有子分类的分类']);
        }
        
        // 删除分类
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
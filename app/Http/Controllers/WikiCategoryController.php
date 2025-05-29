<?php

namespace App\Http\Controllers;

use App\Models\WikiCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * WikiCategoryController
 *
 * 管理 Wiki 分类的创建、读取、更新和删除 (CRUD) 操作。
 */
class WikiCategoryController extends Controller
{
    /**
     * 显示 Wiki 分类列表。
     *
     * @return Response Inertia 响应，包含分类列表。
     */
    public function index(): Response
    {
        // 获取所有 Wiki 分类，并计算每个分类关联的页面数量，同时预加载父分类。
        $categories = WikiCategory::withCount('pages')
            ->with('parent')
            ->orderBy('order')
            ->get();

        // 渲染 Wiki/Categories/Index 页面，并传递分类数据。
        return Inertia::render('Wiki/Categories/Index', [
            'categories' => $categories,
        ]);
    }

    /**
     * 显示创建新 Wiki 分类的表单。
     *
     * @return Response Inertia 响应，包含所有分类以便选择父分类。
     */
    public function create(): Response
    {
        // 获取所有 Wiki 分类，用于在表单中选择父分类。
        $categories = WikiCategory::all();

        // 渲染 Wiki/Categories/Create 页面，并传递所有分类数据。
        return Inertia::render('Wiki/Categories/Create', [
            'categories' => $categories,
        ]);
    }

    /**
     * 在数据库中存储一个新的 Wiki 分类。
     *
     * @param Request $request 包含分类数据的 HTTP 请求。
     * @return \Illuminate\Http\RedirectResponse 重定向到分类列表页。
     */
    public function store(Request $request)
    {
        // 验证传入的请求数据。
        $validated = $request->validate([
            'name' => 'required|string|max:255', // 分类名称，必填，字符串，最大长度255。
            'description' => 'nullable|string', // 分类描述，可空，字符串。
            'parent_id' => 'nullable|exists:wiki_categories,id', // 父分类ID，可空，必须存在于 wiki_categories 表中。
            'order' => 'nullable|integer', // 排序顺序，可空，整数。
        ]);

        // 根据分类名称生成 slug。
        $slug = Str::slug($validated['name']);

        // 检查生成的 slug 是否已存在，如果存在则添加随机字符串以确保唯一性。
        if (WikiCategory::where('slug', $slug)->exists()) {
            $slug = $slug.'-'.Str::random(5);
        }

        // 创建新的 Wiki 分类记录。
        WikiCategory::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'],
            'parent_id' => $validated['parent_id'],
            'order' => $validated['order'] ?? 0, // 如果未提供，默认为0。
        ]);

        // 重定向到分类列表页，并附带成功闪存消息。
        return redirect()->route('wiki.categories.index')
            ->with('flash', [
                'message' => [
                    'type' => 'success',
                    'text' => '分类创建成功！',
                ],
            ]);
    }

    /**
     * 显示编辑指定 Wiki 分类的表单。
     *
     * @param WikiCategory $category 要编辑的 Wiki 分类实例。
     * @return Response Inertia 响应，包含当前分类信息和所有其他分类（作为父分类选项）。
     */
    public function edit(WikiCategory $category): Response
    {
        // 获取除当前分类之外的所有 Wiki 分类，用于在表单中选择新的父分类。
        $categories = WikiCategory::where('id', '!=', $category->id)->get();

        // 渲染 Wiki/Categories/Edit 页面，并传递当前分类和可用父分类数据。
        return Inertia::render('Wiki/Categories/Edit', [
            'category' => $category,
            'categories' => $categories,
        ]);
    }

    /**
     * 更新数据库中指定 Wiki 分类的信息。
     *
     * @param Request $request 包含更新分类数据的 HTTP 请求。
     * @param WikiCategory $category 要更新的 Wiki 分类实例。
     * @return \Illuminate\Http\RedirectResponse 重定向到分类列表页。
     */
    public function update(Request $request, WikiCategory $category)
    {
        // 验证传入的请求数据。
        $validated = $request->validate([
            'name' => 'required|string|max:255', // 分类名称，必填，字符串，最大长度255。
            'description' => 'nullable|string', // 分类描述，可空，字符串。
            'parent_id' => 'nullable|exists:wiki_categories,id', // 父分类ID，可空，必须存在于 wiki_categories 表中。
            'order' => 'nullable|integer', // 排序顺序，可空，整数。
        ]);

        // 防止分类选择自身作为父分类，避免无限循环。
        if ($validated['parent_id'] && $validated['parent_id'] == $category->id) {
            // 如果尝试将自身设为父分类，则返回并附带错误信息。
            return back()->withErrors(['parent_id' => '分类不能选择自己作为父分类']);
        }

        // 更新 Wiki 分类记录。
        $category->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'parent_id' => $validated['parent_id'],
            'order' => $validated['order'] ?? 0, // 如果未提供，默认为0。
        ]);

        // 重定向到分类列表页，并附带成功闪存消息。
        return redirect()->route('wiki.categories.index')
            ->with('flash', [
                'message' => [
                    'type' => 'success',
                    'text' => '分类更新成功！',
                ],
            ]);
    }

    /**
     * 从数据库中删除指定 Wiki 分类。
     *
     * @param WikiCategory $category 要删除的 Wiki 分类实例。
     * @return \Illuminate\Http\RedirectResponse 重定向到分类列表页。
     */
    public function destroy(WikiCategory $category)
    {
        // 检查当前分类是否含有子分类。
        if ($category->children()->exists()) {
            // 如果存在子分类，则不允许删除，返回并附带错误信息。
            return back()->withErrors(['general' => '无法删除含有子分类的分类']);
        }

        // 删除 Wiki 分类记录。
        $category->delete();

        // 重定向到分类列表页，并附带成功闪存消息。
        return redirect()->route('wiki.categories.index')
            ->with('flash', [
                'message' => [
                    'type' => 'success',
                    'text' => '分类删除成功！',
                ],
            ]);
    }
}
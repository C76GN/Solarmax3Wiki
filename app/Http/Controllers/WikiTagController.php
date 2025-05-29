<?php

namespace App\Http\Controllers;

use App\Models\WikiTag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Wiki标签控制器
 *
 * 负责处理Wiki标签的创建、显示、更新和删除操作。
 */
class WikiTagController extends Controller
{
    /**
     * 显示所有Wiki标签的列表。
     *
     * @return Response Inertia响应，包含标签数据。
     */
    public function index(): Response
    {
        // 获取所有标签，并统计每个标签关联的页面数量
        $tags = WikiTag::withCount('pages')->get();

        // 渲染Wiki/Tags/Index页面并传递标签数据
        return Inertia::render('Wiki/Tags/Index', [
            'tags' => $tags,
        ]);
    }

    /**
     * 存储一个新的Wiki标签。
     *
     * @param Request $request HTTP请求实例。
     * @return \Illuminate\Http\RedirectResponse 重定向到标签列表页。
     */
    public function store(Request $request)
    {
        // 验证请求数据，确保标签名称符合规则且唯一
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:wiki_tags,name',
        ]);

        // 根据标签名称生成唯一的slug
        $slug = Str::slug($validated['name']);

        // 创建新的Wiki标签记录
        WikiTag::create([
            'name' => $validated['name'],
            'slug' => $slug,
        ]);

        // 重定向回标签列表页，并附带成功闪存消息
        return redirect()->route('wiki.tags.index')
            ->with('flash', [
                'message' => [
                    'type' => 'success',
                    'text' => '标签创建成功！',
                ],
            ]);
    }

    /**
     * 更新指定的Wiki标签。
     *
     * @param Request $request HTTP请求实例。
     * @param WikiTag $tag 要更新的标签实例。
     * @return \Illuminate\Http\RedirectResponse 重定向到标签列表页。
     */
    public function update(Request $request, WikiTag $tag)
    {
        // 验证请求数据，确保标签名称符合规则且对当前标签唯一
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:wiki_tags,name,'.$tag->id,
        ]);

        // 根据新名称生成更新后的slug
        $slug = Str::slug($validated['name']);

        // 更新标签信息
        $tag->update([
            'name' => $validated['name'],
            'slug' => $slug,
        ]);

        // 重定向回标签列表页，并附带成功闪存消息
        return redirect()->route('wiki.tags.index')
            ->with('flash', [
                'message' => [
                    'type' => 'success',
                    'text' => '标签更新成功！',
                ],
            ]);
    }

    /**
     * 从数据库中删除指定的Wiki标签。
     *
     * @param WikiTag $tag 要删除的标签实例。
     * @return \Illuminate\Http\RedirectResponse 重定向到标签列表页。
     */
    public function destroy(WikiTag $tag)
    {
        // 删除标签
        $tag->delete();

        // 重定向回标签列表页，并附带成功闪存消息
        return redirect()->route('wiki.tags.index')
            ->with('flash', [
                'message' => [
                    'type' => 'success',
                    'text' => '标签删除成功！',
                ],
            ]);
    }
}
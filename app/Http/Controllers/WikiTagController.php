<?php

namespace App\Http\Controllers;

use App\Models\WikiTag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class WikiTagController extends Controller
{
    // 显示标签列表
    public function index(): Response
    {

        $tags = WikiTag::withCount('pages')->get();

        return Inertia::render('Wiki/Tags/Index', [
            'tags' => $tags,
        ]);
    }

    // 存储新标签
    public function store(Request $request)
    {

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:wiki_tags,name',
        ]);

        // 生成slug
        $slug = Str::slug($validated['name']);

        WikiTag::create([
            'name' => $validated['name'],
            'slug' => $slug,
        ]);

        return redirect()->route('wiki.tags.index')
            ->with('flash', [
                'message' => [
                    'type' => 'success',
                    'text' => '标签创建成功！',
                ],
            ]);
    }

    // 更新标签
    public function update(Request $request, WikiTag $tag)
    {

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:wiki_tags,name,'.$tag->id,
        ]);

        // 生成slug
        $slug = Str::slug($validated['name']);

        $tag->update([
            'name' => $validated['name'],
            'slug' => $slug,
        ]);

        return redirect()->route('wiki.tags.index')
            ->with('flash', [
                'message' => [
                    'type' => 'success',
                    'text' => '标签更新成功！',
                ],
            ]);
    }

    // 删除标签
    public function destroy(WikiTag $tag)
    {

        $tag->delete();

        return redirect()->route('wiki.tags.index')
            ->with('flash', [
                'message' => [
                    'type' => 'success',
                    'text' => '标签删除成功！',
                ],
            ]);
    }
}

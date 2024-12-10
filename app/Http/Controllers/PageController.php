<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Template;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PageController extends Controller
{
    public function index()
    {
        $pages = Page::with('template')
            ->latest()
            ->paginate(10);
        
        return Inertia::render('Pages/Index', [
            'pages' => $pages
        ]);
    }

    public function create()
    {
        $templates = Template::all();
        
        return Inertia::render('Pages/Create', [
            'templates' => $templates
        ]);
    }

    public function store(Request $request)
    {
        $template = Template::findOrFail($request->template_id);
        
        // 基础验证
        $validated = $request->validate([
            'template_id' => 'required|exists:templates,id',
            'title' => [
                'required',
                'string',
                'max:255',
                Rule::unique('pages', 'title')->whereNull('deleted_at')
            ],
            'content' => 'required|array'
        ]);

        // 根据模板字段验证内容
        $contentRules = [];
        foreach ($template->fields as $field) {
            $rule = ['required' => $field['required']];
            
            switch ($field['type']) {
                case 'number':
                    $contentRules["content.{$field['name']}"] = array_filter([
                        $field['required'] ? 'required' : 'nullable',
                        'numeric'
                    ]);
                    break;
                case 'date':
                    $contentRules["content.{$field['name']}"] = array_filter([
                        $field['required'] ? 'required' : 'nullable',
                        'date'
                    ]);
                    break;
                case 'boolean':
                    $contentRules["content.{$field['name']}"] = array_filter([
                        $field['required'] ? 'required' : 'nullable',
                        'boolean'
                    ]);
                    break;
                default:
                    $contentRules["content.{$field['name']}"] = array_filter([
                        $field['required'] ? 'required' : 'nullable',
                        'string'
                    ]);
            }
        }

        $request->validate($contentRules);

        // 创建页面
        $page = new Page($validated);
        $page->slug = Str::slug($validated['title']);
        $page->save();

        return redirect()->route('pages.index')
            ->with('flash', [
                'message' => [
                    'type' => 'success',
                    'text' => '页面创建成功！'
                ]
            ]);
    }

    public function edit(Page $page)
    {
        $page->load('template');
        $templates = Template::all();

        return Inertia::render('Pages/Edit', [
            'page' => $page,
            'templates' => $templates
        ]);
    }

    public function update(Request $request, Page $page)
    {
        $template = Template::findOrFail($request->template_id);

        // 基础验证
        $validated = $request->validate([
            'template_id' => 'required|exists:templates,id',
            'title' => [
                'required',
                'string',
                'max:255',
                Rule::unique('pages')->whereNull('deleted_at')->ignore($page->id)
            ],
            'content' => 'required|array'
        ]);

        // 根据模板字段验证内容
        $contentRules = [];
        foreach ($template->fields as $field) {
            $rule = ['required' => $field['required']];
            
            switch ($field['type']) {
                case 'number':
                    $contentRules["content.{$field['name']}"] = array_filter([
                        $field['required'] ? 'required' : 'nullable',
                        'numeric'
                    ]);
                    break;
                case 'date':
                    $contentRules["content.{$field['name']}"] = array_filter([
                        $field['required'] ? 'required' : 'nullable',
                        'date'
                    ]);
                    break;
                case 'boolean':
                    $contentRules["content.{$field['name']}"] = array_filter([
                        $field['required'] ? 'required' : 'nullable',
                        'boolean'
                    ]);
                    break;
                default:
                    $contentRules["content.{$field['name']}"] = array_filter([
                        $field['required'] ? 'required' : 'nullable',
                        'string'
                    ]);
            }
        }

        $request->validate($contentRules);

        // 更新页面
        $page->update($validated);

        return redirect()->route('pages.index')
            ->with('flash', [
                'message' => [
                    'type' => 'success',
                    'text' => '页面更新成功！'
                ]
            ]);
    }

    public function destroy(Page $page)
    {
        $page->delete();

        return redirect()->route('pages.index')
            ->with('flash', [
                'message' => [
                    'type' => 'success',
                    'text' => '页面删除成功！'
                ]
            ]);
    }

    public function publish(Page $page)
    {
        $page->update([
            'is_published' => true,
            'published_at' => now()
        ]);
        $page->logCustomActivity('publish');
        return redirect()->route('pages.index')
            ->with('flash', [
                'message' => [
                    'type' => 'success',
                    'text' => '页面已发布！'
                ]
            ]);
    }

    public function unpublish(Page $page)
    {
        $page->update([
            'is_published' => false,
            'published_at' => null
        ]);

        return redirect()->route('pages.index')
            ->with('flash', [
                'message' => [
                    'type' => 'success',
                    'text' => '页面已取消发布！'
                ]
            ]);
    }
}
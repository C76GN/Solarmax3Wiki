<?php

namespace App\Http\Controllers;

use App\Models\Template;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TemplateController extends Controller
{
    public function index()
    {
        $templates = Template::latest()->paginate(10);
        
        return Inertia::render('Templates/Index', [
            'templates' => $templates
        ]);
    }

    public function create()
    {
        return Inertia::render('Templates/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('templates')->whereNull('deleted_at')
            ],
            'description' => 'required|string',
            'fields' => 'required|array|min:1',
            'fields.*.name' => 'required|string|max:255',
            'fields.*.type' => 'required|string|in:text,number,boolean,date,markdown',
            'fields.*.required' => 'required|boolean'
        ]);

        $slug = Str::slug($validated['name']);
        
        // 检查 slug 是否已存在（考虑软删除）
        if (Template::withTrashed()->where('slug', $slug)->whereNull('deleted_at')->exists()) {
            // 如果存在，生成一个唯一的 slug
            $slug = Str::slug($validated['name']) . '-' . uniqid();
        }

        $validated['slug'] = $slug;
        $validated['fields'] = array_values($validated['fields']);

        Template::create($validated);

        return redirect()->route('templates.index')
            ->with('flash', [
                'message' => [
                    'type' => 'success',
                    'text' => '模板创建成功！'
                ]
            ]);
    }

    public function edit(Template $template)
    {
        return Inertia::render('Templates/Edit', [
            'template' => $template
        ]);
    }

    public function update(Request $request, Template $template)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('templates')->whereNull('deleted_at')->ignore($template->id)
            ],
            'description' => 'required|string',
            'fields' => 'required|array|min:1',
            'fields.*.name' => 'required|string|max:255',
            'fields.*.type' => 'required|string|in:text,number,boolean,date,markdown',
            'fields.*.required' => 'required|boolean'
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['fields'] = array_values($validated['fields']);

        $template->update($validated);

        return redirect()->route('templates.index')
            ->with('flash', [
                'message' => [
                    'type' => 'success',
                    'text' => '模板更新成功！'
                ]
            ]);
    }

    public function destroy(Template $template)
    {
        $template->delete();

        return redirect()->route('templates.index')
            ->with('flash', [
                'message' => [
                    'type' => 'success',
                    'text' => '模板删除成功！'
                ]
            ]);
    }
}
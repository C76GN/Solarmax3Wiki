<?php

namespace App\Http\Controllers;

use App\Models\WikiTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class WikiTemplateController extends Controller
{
    // 显示模板列表
    public function index(): Response
    {
        $this->authorize('wiki.manage_templates');
        
        $templates = WikiTemplate::withCount('pages')
            ->with('creator')
            ->latest()
            ->get();
            
        return Inertia::render('Wiki/Templates/Index', [
            'templates' => $templates
        ]);
    }
    
    // 显示创建模板表单
    public function create(): Response
    {
        $this->authorize('wiki.manage_templates');
        
        return Inertia::render('Wiki/Templates/Create');
    }
    
    // 存储新模板
    public function store(Request $request)
    {
        $this->authorize('wiki.manage_templates');
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'structure' => 'required|array'
        ]);
        
        // 生成slug
        $slug = Str::slug($validated['name']);
        
        // 检查slug是否存在，如果存在则添加随机字符串
        if (WikiTemplate::where('slug', $slug)->exists()) {
            $slug = $slug . '-' . Str::random(5);
        }
        
        WikiTemplate::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'],
            'structure' => $validated['structure'],
            'created_by' => auth()->id()
        ]);
        
        return redirect()->route('wiki.templates.index')
            ->with('flash', [
                'message' => [
                    'type' => 'success',
                    'text' => '模板创建成功！'
                ]
            ]);
    }
    
    // 显示编辑模板表单
    public function edit(WikiTemplate $template): Response
    {
        $this->authorize('wiki.manage_templates');
        
        return Inertia::render('Wiki/Templates/Edit', [
            'template' => $template
        ]);
    }
    
    // 更新模板
    public function update(Request $request, WikiTemplate $template)
    {
        $this->authorize('wiki.manage_templates');
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'structure' => 'required|array'
        ]);
        
        $template->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'structure' => $validated['structure']
        ]);
        
        return redirect()->route('wiki.templates.index')
            ->with('flash', [
                'message' => [
                    'type' => 'success',
                    'text' => '模板更新成功！'
                ]
            ]);
    }
    
    // 删除模板
    public function destroy(WikiTemplate $template)
    {
        $this->authorize('wiki.manage_templates');
        
        // 检查是否有页面使用此模板
        if ($template->pages()->exists()) {
            return back()->withErrors(['general' => '无法删除正在使用中的模板']);
        }
        
        $template->delete();
        
        return redirect()->route('wiki.templates.index')
            ->with('flash', [
                'message' => [
                    'type' => 'success',
                    'text' => '模板删除成功！'
                ]
            ]);
    }
}
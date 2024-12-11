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
    public function show(Template $template)
    {
        return Inertia::render('Templates/Show', [
            'template' => $template
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
    
    public function export($id)
    {
        $template = Template::findOrFail($id);
        
        // 准备导出数据
        $exportData = [
            'name' => $template->name,
            'description' => $template->description,
            'fields' => $template->fields,
            'exported_at' => now()->toIso8601String(),
            'version' => '1.0'
        ];
        
        // 生成文件名
        $fileName = Str::slug($template->name) . '-' . now()->format('Y-m-d') . '.json';
        
        // 返回文件下载响应
        return response()->streamDownload(function () use ($exportData) {
            echo json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $fileName, [
            'Content-Type' => 'application/json',
            'X-Inertia-Location' => request()->fullUrl(),
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'template_file' => 'required|file|mimes:json|max:1024', // 最大1MB
        ]);

        try {
            // 读取并解析JSON文件
            $content = json_decode(file_get_contents($request->file('template_file')), true);
            
            if (!$content || !isset($content['name']) || !isset($content['fields'])) {
                throw new \Exception('Invalid template file format');
            }

            // 检查名称是否已存在
            $baseName = $content['name'];
            $name = $baseName;
            $counter = 1;
            
            while (Template::where('name', $name)->exists()) {
                $name = $baseName . ' (' . $counter . ')';
                $counter++;
            }

            // 创建新模板
            $template = Template::create([
                'name' => $name,
                'description' => $content['description'] ?? '',
                'fields' => $content['fields'],
                'slug' => Str::slug($name)
            ]);

            return redirect()->route('templates.index')
                ->with('flash', [
                    'message' => [
                        'type' => 'success',
                        'text' => '模板导入成功！'
                    ]
                ]);

        } catch (\Exception $e) {
            return redirect()->route('templates.index')
                ->with('flash', [
                    'message' => [
                        'type' => 'error',
                        'text' => '模板导入失败：' . $e->getMessage()
                    ]
                ]);
        }
    }
}
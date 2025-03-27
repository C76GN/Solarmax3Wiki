<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Wiki页面更新请求
 * 
 * 处理更新已有Wiki页面的表单验证，
 * 包括权限检查和输入数据验证
 */
class UpdateWikiPageRequest extends FormRequest
{
    /**
     * 确定用户是否有权发起此请求
     * 
     * 检查当前用户是否拥有编辑Wiki页面的权限
     *
     * @return bool 是否授权
     */
    public function authorize(): bool
    {
        return $this->user()->hasPermission('wiki.edit');
    }

    /**
     * 获取适用于请求的验证规则
     * 
     * 定义页面标题、内容、分类和更新控制参数的验证规则
     *
     * @return array 验证规则
     */
    public function rules(): array
    {
        // 获取当前页面ID
        $pageId = $this->route('page')->id;
        
        return [
            // 标题：必填、字符串、最大255字符、在wiki_pages表中唯一（忽略当前页面）
            'title' => ['required', 'string', 'max:255', Rule::unique('wiki_pages')->ignore($pageId)],
            
            // 内容：必填、字符串
            'content' => 'required|string',
            
            // 分类：可选、数组
            'categories' => 'nullable|array',
            
            // 分类数组元素：可选、必须在wiki_categories表中存在
            'categories.*' => 'nullable|exists:wiki_categories,id',
            
            // 最后检查时间：用于冲突检测的时间戳
            'last_check' => 'nullable|date',
            
            // 强制更新标志：用于绕过冲突检测
            'force_update' => 'boolean|nullable'
        ];
    }
}
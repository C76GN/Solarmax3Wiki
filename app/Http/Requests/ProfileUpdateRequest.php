<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 个人资料更新请求
 * 
 * 处理用户个人资料更新的表单验证，
 * 包括姓名和电子邮件的验证规则
 */
class ProfileUpdateRequest extends FormRequest
{
    /**
     * 获取适用于请求的验证规则
     * 
     * 定义姓名和电子邮件的验证规则，
     * 确保电子邮件在用户表中是唯一的（忽略当前用户）
     *
     * @return array 验证规则
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                // 确保电子邮件在用户表中唯一（忽略当前用户）
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
        ];
    }
}
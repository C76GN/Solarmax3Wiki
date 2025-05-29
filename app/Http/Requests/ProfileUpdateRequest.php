<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 个人资料更新请求
 *
 * 此 Form Request 类用于处理用户更新个人资料时的表单数据验证。
 * 它定义了更新用户姓名和电子邮件的验证规则，确保数据的合法性和完整性。
 */
class ProfileUpdateRequest extends FormRequest
{
    /**
     * 获取请求中数据的验证规则。
     *
     * 此方法定义了更新用户个人资料时所需遵循的验证规则集合，
     * 包括姓名的必填、字符串类型和最大长度限制，
     * 以及电子邮件的必填、字符串类型、小写格式、有效邮箱格式、最大长度，
     * 并特别处理了电子邮件的唯一性检查，允许当前用户保留其原有邮箱。
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array<mixed>|string> 定义验证规则的数组
     */
    public function rules(): array
    {
        return [
            // 姓名规则：必填、字符串类型、最大长度255
            'name' => ['required', 'string', 'max:255'],
            // 电子邮件规则
            'email' => [
                'required',     // 必填
                'string',       // 字符串类型
                'lowercase',    // 必须为小写字母
                'email',        // 必须是有效的电子邮件格式
                'max:255',      // 最大长度255
                // 电子邮件必须在 users 表中唯一，但允许忽略当前用户的邮箱（即用户可以不更改邮箱）
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
        ];
    }
}
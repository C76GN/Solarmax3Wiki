<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * 密码管理控制器
 *
 * 处理用户密码更新操作
 */
class PasswordController extends Controller
{
    /**
     * 更新用户密码
     *
     * @param  Request  $request  请求对象
     */
    public function update(Request $request): RedirectResponse
    {
        // 验证请求数据
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        // 更新用户密码
        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        // 返回上一页
        return back();
    }
}

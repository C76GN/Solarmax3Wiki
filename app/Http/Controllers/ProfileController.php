<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

/**
 * 用户个人资料控制器
 *
 * 负责处理用户个人资料的查看、编辑和删除功能
 */
class ProfileController extends Controller
{
    /**
     * 显示用户个人资料编辑页面
     *
     * @param  Request  $request  当前请求实例
     * @return Response Inertia渲染的响应
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * 更新用户个人资料信息
     *
     * 如果邮箱被更改，则将邮箱验证状态重置为未验证
     *
     * @param  ProfileUpdateRequest  $request  包含验证规则的请求实例
     * @return RedirectResponse 重定向响应
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        // 填充已验证的用户数据
        $request->user()->fill($request->validated());

        // 如果邮箱已更改，重置验证状态
        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        // 保存用户数据
        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * 删除用户账户
     *
     * 验证密码后删除用户账户并退出登录
     *
     * @param  Request  $request  当前请求实例
     * @return RedirectResponse 重定向响应
     */
    public function destroy(Request $request): RedirectResponse
    {
        // 验证用户输入的当前密码
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        // 获取当前用户
        $user = $request->user();

        // 注销登录
        Auth::logout();

        // 删除用户账户
        $user->delete();

        // 使会话失效并重新生成令牌
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}

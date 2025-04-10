<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 邮箱验证通知控制器
 *
 * 处理用户重新发送邮箱验证链接的请求
 */
class EmailVerificationNotificationController extends Controller
{
    /**
     * 重新发送邮箱验证通知
     *
     * @param  Request  $request  请求对象
     */
    public function store(Request $request): RedirectResponse
    {
        // 如果用户邮箱已验证，直接重定向到目标页面
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        // 发送邮箱验证通知
        $request->user()->sendEmailVerificationNotification();

        // 返回上一页并附带状态消息
        return back()->with('status', 'verification-link-sent');
    }
}

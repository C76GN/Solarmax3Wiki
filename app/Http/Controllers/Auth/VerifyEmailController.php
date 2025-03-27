<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

/**
 * 处理邮箱验证的控制器
 */
class VerifyEmailController extends Controller
{
    /**
     * 处理邮箱验证的请求
     *
     * 当用户点击验证邮件中的链接时，验证用户邮箱
     * 如果邮箱验证成功，触发Verified事件
     *
     * @param EmailVerificationRequest $request 邮箱验证请求
     * @return RedirectResponse 重定向响应
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        // 如果用户邮箱已经验证过，直接重定向到仪表盘
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
        }

        // 标记用户邮箱为已验证并触发Verified事件
        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        // 重定向到仪表盘并带上verified参数
        return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
    }
}
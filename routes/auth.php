<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

/**
 * 身份验证路由
 *
 * 本文件定义了所有与用户身份验证相关的路由，包括：
 * - 用户注册与登录
 * - 密码重置流程
 * - 邮箱验证
 * - 会话管理
 */

/**
 * 游客路由组
 *
 * 这些路由只对未登录的用户可用
 */
Route::middleware('guest')->group(function () {
    /**
     * 用户注册路由
     */
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);

    /**
     * 用户登录路由
     */
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    /**
     * 密码重置流程路由
     */
    // 请求密码重置链接
    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    // 重置密码表单
    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

/**
 * 已认证用户路由组
 *
 * 这些路由只对已登录的用户可用
 */
Route::middleware('auth')->group(function () {
    /**
     * 邮箱验证路由
     */
    // 验证邮箱提示页面
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    // 验证邮箱链接处理
    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1']) // 签名链接 + 频率限制
        ->name('verification.verify');

    // 重新发送验证邮件
    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1') // 防止滥用
        ->name('verification.send');

    /**
     * 密码确认与修改路由
     */
    // 确认密码（敏感操作前）
    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');
    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    // 更新密码
    Route::put('password', [PasswordController::class, 'update'])
        ->name('password.update');

    /**
     * 登出路由
     */
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});

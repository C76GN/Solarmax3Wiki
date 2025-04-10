<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * 新密码设置控制器
 *
 * 处理用户密码重置流程的最后阶段
 */
class NewPasswordController extends Controller
{
    /**
     * 显示设置新密码的表单页面
     *
     * @param  Request  $request  请求对象
     */
    public function create(Request $request): Response
    {
        return Inertia::render('Auth/ResetPassword', [
            'email' => $request->email,
            'token' => $request->route('token'),
        ]);
    }

    /**
     * 处理重置密码请求
     *
     * @param  Request  $request  请求对象
     *
     * @throws ValidationException 当密码重置失败时抛出异常
     */
    public function store(Request $request): RedirectResponse
    {
        // 验证请求数据
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // 执行密码重置
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                // 触发密码重置事件
                event(new PasswordReset($user));
            }
        );

        // 处理重置结果
        if ($status == Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', __($status));
        }

        // 如果重置失败，抛出异常
        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

/**
 * 用户注册控制器
 *
 * 处理新用户的注册流程
 */
class RegisteredUserController extends Controller
{
    /**
     * 显示用户注册页面
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * 处理用户注册请求
     *
     * @param  Request  $request  请求对象
     */
    public function store(Request $request): RedirectResponse
    {
        // 验证请求数据
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // 创建新用户
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // 触发用户注册事件
        event(new Registered($user));

        // 自动登录新注册用户
        Auth::login($user);

        // 重定向到仪表盘
        return redirect(route('dashboard', absolute: false));
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

/**
 * 用户认证会话控制器
 * 
 * 处理用户登录、退出等认证会话相关功能
 */
class AuthenticatedSessionController extends Controller
{
    /**
     * 显示登录页面
     *
     * @return Response
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * 处理登录认证请求
     *
     * @param LoginRequest $request 登录请求对象
     * @return RedirectResponse
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();
        
        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * 处理用户退出登录请求
     *
     * @param Request $request 请求对象
     * @return RedirectResponse
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect('/');
    }
}
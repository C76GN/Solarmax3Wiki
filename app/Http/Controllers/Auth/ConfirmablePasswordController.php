<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
* 密码确认控制器
* 
* 用于处理敏感操作前的密码确认功能
*/
class ConfirmablePasswordController extends Controller
{
   /**
    * 显示密码确认页面
    *
    * @return Response
    */
   public function show(): Response
   {
       return Inertia::render('Auth/ConfirmPassword');
   }

   /**
    * 验证用户密码
    *
    * @param Request $request 请求对象
    * @return RedirectResponse
    * @throws ValidationException 密码验证失败时抛出异常
    */
   public function store(Request $request): RedirectResponse
   {
       // 验证用户的密码
       if (! Auth::guard('web')->validate([
           'email' => $request->user()->email,
           'password' => $request->password,
       ])) {
           throw ValidationException::withMessages([
               'password' => __('auth.password'),
           ]);
       }
       
       // 在会话中标记密码已确认
       $request->session()->put('auth.password_confirmed_at', time());
       
       // 重定向到用户原本想访问的页面或默认页面
       return redirect()->intended(route('dashboard', absolute: false));
   }
}
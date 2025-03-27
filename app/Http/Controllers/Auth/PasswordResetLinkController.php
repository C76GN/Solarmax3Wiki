<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
* 密码重置链接控制器
* 
* 处理密码重置流程的第一步：发送重置链接
*/
class PasswordResetLinkController extends Controller
{
   /**
    * 显示请求密码重置链接的表单页面
    *
    * @return Response
    */
   public function create(): Response
   {
       return Inertia::render('Auth/ForgotPassword', [
           'status' => session('status'),
       ]);
   }

   /**
    * 处理发送密码重置链接的请求
    *
    * @param Request $request 请求对象
    * @return RedirectResponse
    * @throws ValidationException 当发送重置链接失败时抛出异常
    */
   public function store(Request $request): RedirectResponse
   {
       // 验证请求数据
       $request->validate([
           'email' => 'required|email',
       ]);

       // 发送密码重置链接
       $status = Password::sendResetLink(
           $request->only('email')
       );

       // 处理发送结果
       if ($status == Password::RESET_LINK_SENT) {
           return back()->with('status', __($status));
       }

       // 如果发送失败，抛出异常
       throw ValidationException::withMessages([
           'email' => [trans($status)],
       ]);
   }
}
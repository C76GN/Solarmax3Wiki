<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
* 邮箱验证提示控制器
* 
* 根据用户邮箱验证状态显示适当的页面
*/
class EmailVerificationPromptController extends Controller
{
   /**
    * 处理邮箱验证提示请求
    *
    * 如果用户邮箱已验证，则重定向到目标页面
    * 否则显示邮箱验证页面
    *
    * @param Request $request 请求对象
    * @return RedirectResponse|Response 重定向或页面响应
    */
   public function __invoke(Request $request): RedirectResponse|Response
   {
       return $request->user()->hasVerifiedEmail()
                   ? redirect()->intended(route('dashboard', absolute: false))
                   : Inertia::render('Auth/VerifyEmail', ['status' => session('status')]);
   }
}
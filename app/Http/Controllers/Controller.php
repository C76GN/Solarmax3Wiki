<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Inertia\Inertia;
use Inertia\Response;

/**
 * 应用控制器基类
 * 
 * 所有应用控制器都应继承此类，
 * 它提供了通用的授权和验证功能
 */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
    
    /**
     * 返回未授权错误页面响应
     *
     * 当用户尝试访问无权限的资源时使用此方法返回统一的错误页面
     * 
     * @param string $message 显示给用户的错误信息
     * @return Response 未授权页面的Inertia响应
     */
    protected function unauthorized(string $message = '您没有权限执行此操作'): Response
    {
        return Inertia::render('Error/Unauthorized', [
            'message' => $message
        ]);
    }
}
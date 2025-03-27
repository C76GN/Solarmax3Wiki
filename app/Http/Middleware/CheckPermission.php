<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Inertia\Inertia;

/**
 * 权限检查中间件
 * 
 * 用于路由权限控制，检查用户是否拥有指定的权限，
 * 如果用户未登录或没有所需权限，则返回403错误响应
 */
class CheckPermission
{
    /**
     * 处理请求并检查用户权限
     *
     * @param Request $request 当前请求实例
     * @param Closure $next 下一个中间件闭包
     * @param string $permission 需要检查的权限名称
     * @return mixed 下一个中间件的响应或403错误响应
     */
    public function handle(Request $request, Closure $next, string $permission): mixed
    {
        $user = $request->user();
        
        // 检查用户是否登录并拥有所需权限
        if (!$user || !$user->hasPermission($permission)) {
            // 对API请求返回JSON格式的错误响应
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => '权限不足',
                ], SymfonyResponse::HTTP_FORBIDDEN);
            }
            
            // 对Web请求返回Inertia渲染的错误页面
            return Inertia::render('Error/Unauthorized', [
                'message' => '您没有权限执行此操作'
            ])->toResponse($request)->setStatusCode(SymfonyResponse::HTTP_FORBIDDEN);
        }
        
        // 用户具有所需权限，继续处理请求
        return $next($request);
    }
}
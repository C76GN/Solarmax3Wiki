<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * 用户角色检查中间件
 *
 * 此中间件用于验证当前登录用户是否拥有访问特定路由所需的角色。
 * 如果用户未登录或不具备指定角色，将返回 403 Forbidden 响应。
 */
class CheckRole
{
    /**
     * 处理传入的请求。
     *
     * 检查当前请求的用户是否拥有指定角色。
     * 如果用户未登录或不具备该角色，则根据请求类型返回 JSON 错误或渲染未授权页面。
     *
     * @param  Request  $request  当前 HTTP 请求实例。
     * @param  Closure  $next  下一个中间件或路由处理程序。
     * @param  string  $role  要求用户拥有的角色名称。
     * @return mixed  返回下一个中间件的响应或未授权错误响应。
     */
    public function handle(Request $request, Closure $next, string $role): mixed
    {
        // 获取当前登录用户实例
        $user = $request->user();

        // 判断用户是否未登录或不拥有指定角色
        if (! $user || ! $user->hasRole($role)) {
            // 如果是 AJAX 或 API 请求，返回 JSON 格式的权限不足错误
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => '权限不足', // 权限不足的中文提示
                ], SymfonyResponse::HTTP_FORBIDDEN);
            }

            // 如果是 Inertia (Web) 请求，渲染未授权错误页面
            return Inertia::render('Error/Unauthorized', [
                'message' => '您没有所需的角色权限', // 未授权页面显示的中文消息
            ])->toResponse($request)->setStatusCode(SymfonyResponse::HTTP_FORBIDDEN);
        }

        // 用户通过权限验证，将请求传递给下一个中间件或路由处理器
        return $next($request);
    }
}
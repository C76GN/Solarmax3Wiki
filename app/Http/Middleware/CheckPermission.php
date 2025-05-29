<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * 权限检查中间件。
 *
 * 此中间件用于验证当前认证用户是否拥有访问特定路由所需的权限。
 * 如果用户不具备所需权限，则根据请求类型返回 JSON 错误或渲染 Inertia 错误页面。
 */
class CheckPermission
{
    /**
     * 处理传入的 HTTP 请求。
     *
     * 检查当前认证用户是否拥有指定权限。
     *
     * @param Request $request 当前 HTTP 请求实例。
     * @param Closure $next 指向链中下一个中间件或控制器的闭包。
     * @param string $permission 路由定义中指定的权限名称。
     * @return mixed 允许请求继续，或返回一个 HTTP 响应以终止请求。
     */
    public function handle(Request $request, Closure $next, string $permission): mixed
    {
        // 获取当前认证用户实例。
        $user = $request->user();

        // 检查用户是否存在或是否拥有指定权限。
        // `hasPermissionTo()` 方法由 Spatie/laravel-permission 包提供。
        if (! $user || ! $user->hasPermissionTo($permission)) {
            // 如果请求期望 JSON 响应（通常是 API 调用），返回 JSON 格式的错误响应。
            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json([
                    'message' => '权限不足 (Forbidden)',
                ], SymfonyResponse::HTTP_FORBIDDEN);
            }

            // 如果是常规 Web 请求（Inertia 渲染），则渲染一个错误页面。
            return Inertia::render('Error/Unauthorized', [
                'message' => '您没有权限执行此操作 ('.$permission.')',
            ])->toResponse($request)->setStatusCode(SymfonyResponse::HTTP_FORBIDDEN);
        }

        // 如果用户拥有所需权限，则允许请求继续执行。
        return $next($request);
    }
}
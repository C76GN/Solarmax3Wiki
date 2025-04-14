<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): mixed // 参数 $permission 是路由中定义的权限字符串
    {
        // 获取当前认证的用户
        $user = $request->user();

        // 检查用户是否存在以及是否拥有指定权限
        // 使用 Spatie 的 hasPermissionTo() 方法
        if (! $user || ! $user->hasPermissionTo($permission)) { // <--- 修改这里：使用 hasPermissionTo()

            // 如果是 API 请求，返回 JSON 错误
            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json([
                    'message' => '权限不足 (Forbidden)', // 更明确的错误消息
                ], SymfonyResponse::HTTP_FORBIDDEN);
            }

            // 如果是 Web 请求，返回 Inertia 错误页面
            // 确保 Error/Unauthorized 页面存在
            return Inertia::render('Error/Unauthorized', [
                'message' => '您没有权限执行此操作 ('.$permission.')', // 可以带上具体权限名
            ])->toResponse($request)->setStatusCode(SymfonyResponse::HTTP_FORBIDDEN);
        }

        // 用户有权限，继续处理请求
        return $next($request);
    }
}

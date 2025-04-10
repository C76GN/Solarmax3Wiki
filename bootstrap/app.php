<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

/**
 * 应用程序引导文件
 *
 * 配置Laravel应用程序的路由、中间件和异常处理。
 * 这是应用程序启动的核心配置文件。
 */
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        // 定义路由文件路径
        web: __DIR__.'/../routes/web.php',       // Web路由
        api: __DIR__.'/../routes/api.php',       // API路由
        commands: __DIR__.'/../routes/console.php', // 控制台命令
        health: '/up',                         // 健康检查路径
    )
    ->withMiddleware(function (Middleware $middleware) {
        // 配置Web中间件组
        $middleware->web(append: [
            // 添加Inertia请求处理中间件
            \App\Http\Middleware\HandleInertiaRequests::class,
            // 添加预加载资源链接头中间件
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        // 配置API中间件组
        $middleware->api(append: [
            // 确保前端请求状态无关性的中间件
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        // 注册中间件别名，便于路由中使用
        $middleware->alias([
            'permission' => \App\Http\Middleware\CheckPermission::class, // 权限检查
            'role' => \App\Http\Middleware\CheckRole::class,             // 角色检查
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // 异常处理配置
        // 目前没有自定义异常处理逻辑
    })->create(); // 创建并返回应用程序实例

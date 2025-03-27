<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

/**
 * Inertia请求处理中间件
 * 
 * 负责处理Inertia.js请求，在服务端与客户端之间共享数据，
 * 并为所有Inertia响应提供一致的配置
 */
class HandleInertiaRequests extends Middleware
{
    /**
     * 定义Inertia响应的根视图
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * 确定当前资源版本
     * 
     * 在Inertia应用中用于资源缓存失效
     *
     * @param Request $request 当前请求实例
     * @return string|null 资源版本
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * 定义在所有Inertia响应中共享的数据
     * 
     * 包括CSRF令牌和认证用户信息
     *
     * @param Request $request 当前请求实例
     * @return array 共享数据
     */
    public function share(Request $request): array
    {
        // 获取当前认证用户
        $user = $request->user();
        
        return array_merge(parent::share($request), [
            // CSRF令牌，用于表单提交安全验证
            'csrf' => csrf_token(),
            
            // 认证信息，包括用户详情和权限
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'permissions' => $user->getAllPermissionsAttribute(),
                    'roles' => $user->roles->pluck('name'),
                ] : null,
            ],
        ]);
    }
}
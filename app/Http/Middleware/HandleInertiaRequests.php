<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

/**
 * Inertia 请求处理中间件。
 *
 * 此中间件用于初始化 Inertia 应用并共享全局数据到前端。
 * 它继承自 Inertia 的基类中间件，并负责设置根视图、管理资产版本
 * 以及定义默认共享的 Inertia props。
 */
class HandleInertiaRequests extends Middleware
{
    /**
     * 首次页面访问时加载的根视图模板。
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * 确定当前资产（CSS/JS）的版本。
     *
     * @param Request $request 当前 HTTP 请求实例。
     * @return string|null 资产版本字符串，或 null。
     */
    public function version(Request $request): ?string
    {
        // 调用父类的版本方法，通常用于缓存清除
        return parent::version($request);
    }

    /**
     * 定义默认共享到前端的 Inertia props。
     *
     * 这些数据将在所有 Inertia 页面请求中可用。
     *
     * @param Request $request 当前 HTTP 请求实例。
     * @return array<string, mixed> 共享给前端的 props 数组。
     */
    public function share(Request $request): array
    {
        // 获取当前认证用户实例
        $user = $request->user();

        // 合并父类共享的 props 和当前应用特定的共享数据
        return array_merge(parent::share($request), [
            // 共享 CSRF token，用于表单提交安全
            'csrf' => csrf_token(),

            // 共享认证用户相关信息
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    // 获取用户所有权限的名称列表
                    'permissions' => $user->getAllPermissions()->pluck('name')->toArray() ?? [],
                    // 获取用户所有角色的名称列表
                    'roles' => $user->roles->pluck('name')->toArray() ?? [],
                ] : null, // 如果用户未登录则为 null
            ],

            // 共享会话中的一次性（flash）消息
            'flash' => function () use ($request) {
                return [
                    'message' => $request->session()->get('flash.message'),
                ];
            },

            // 共享会话中的验证错误信息
            'errors' => function () use ($request) {
                return $request->session()->get('errors')
                    ? $request->session()->get('errors')->getBag('default')->getMessages()
                    : (object) []; // 如果没有错误，则返回空对象
            },

            // 共享 Ziggy 路由配置，使 Laravel 路由在 JavaScript 中可用
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(), // 包含所有注册的路由信息
                'location' => $request->url(), // 当前页面的完整 URL
            ],
        ]);
    }
}
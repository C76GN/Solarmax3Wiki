<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * 定义根视图模板。
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * 确定当前请求的资源版本。
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * 定义默认情况下与 Inertia 共享的 props。
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return array_merge(parent::share($request), [
            'csrf' => csrf_token(), // 添加 CSRF token
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'permissions' => $user?->getAllPermissionsAttribute() ?? [], // 获取用户所有权限
                    'roles' => $user?->roles?->pluck('name') ?? [], // 获取用户角色名称
                ] : null,
            ],
            'flash' => function () use ($request) {
                return [
                    'message' => $request->session()->get('flash.message'), // 添加 flash 消息
                ];
            },
            'errors' => function () use ($request) {
                // 分享验证错误
                return $request->session()->get('errors')
                    ? $request->session()->get('errors')->getBag('default')->getMessages()
                    : (object) [];
            },
            'ziggy' => fn () => [
                // 使用 new Ziggy() 来获取路由信息
                ...(new Ziggy)->toArray(), // 这一行现在应该可以工作了
                'location' => $request->url(), // 当前 URL
            ],
        ]);
    }
}
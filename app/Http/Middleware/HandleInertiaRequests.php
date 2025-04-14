<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return array_merge(parent::share($request), [
            'csrf' => csrf_token(),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    // 使用 Spatie 的 getAllPermissions() 方法，并提取 name
                    'permissions' => $user->getAllPermissions()->pluck('name')->toArray() ?? [], // <--- 修改这里
                    // 获取角色名称列表（保持不变，Spatie 有 roles 关系）
                    'roles' => $user->roles->pluck('name')->toArray() ?? [],
                ] : null,
            ],
            'flash' => function () use ($request) { // 共享 Flash 消息
                return [
                    'message' => $request->session()->get('flash.message'),
                ];
            },
            'errors' => function () use ($request) { // 共享验证错误
                return $request->session()->get('errors')
                    ? $request->session()->get('errors')->getBag('default')->getMessages()
                    : (object) [];
            },
            'ziggy' => fn () => [ // Ziggy 配置用于前端路由
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            // 你可以在这里添加其他需要全局共享的数据
            // 'appName' => config('app.name'),
        ]);
    }
}

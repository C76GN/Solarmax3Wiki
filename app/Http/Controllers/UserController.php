<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * 用户管理控制器
 * 
 * 负责用户列表展示、用户角色分配等用户管理功能
 */
class UserController extends Controller
{
    /**
     * 显示用户列表页面
     *
     * @return Response 用户列表页面
     */
    public function index(): Response
    {
        $users = User::with('roles')
            ->latest()
            ->paginate(10);

        return Inertia::render('Users/Index', [
            'users' => $users
        ]);
    }

    /**
     * 显示编辑用户角色页面
     *
     * @param User $user 要编辑的用户
     * @return Response 编辑用户页面
     */
    public function edit(User $user): Response
    {
        // 获取所有可用角色
        $roles = Role::all();
        
        return Inertia::render('Users/Edit', [
            'user' => array_merge($user->toArray(), [
                'roles' => $user->roles->pluck('id')->toArray()
            ]),
            'roles' => $roles
        ]);
    }

    /**
     * 更新用户角色
     *
     * @param Request $request 包含角色数据的请求
     * @param User $user 要更新的用户
     * @return RedirectResponse 重定向到用户列表
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        // 验证请求数据
        $validated = $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id'
        ]);

        // 同步用户角色
        $user->roles()->sync($validated['roles']);

        // 重定向到用户列表并显示成功消息
        return redirect()->route('users.index')
            ->with('flash', [
                'message' => [
                    'type' => 'success',
                    'text' => '用户角色更新成功！'
                ]
            ]);
    }
}
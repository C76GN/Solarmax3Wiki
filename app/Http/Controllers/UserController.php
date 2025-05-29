<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * 显示用户列表页面。
     *
     * 通过分页获取用户数据，并加载其关联的角色信息，
     * 同时检查当前用户是否有编辑权限以控制前端UI。
     */
    public function index(): InertiaResponse
    {
        // 授权检查：确保当前用户有查看所有用户的权限
        $this->authorize('viewAny', User::class);

        // 检查当前用户是否拥有编辑权限，用于前端UI控制
        $canEdit = Auth::check() && Auth::user()->can('user.edit');

        // 获取所有用户，并预加载其关联的角色信息
        $users = User::with('roles:id,name')
            ->latest()
            ->paginate(10)
            // 格式化用户数据，添加角色显示名称和系统角色标识
            ->through(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at,
                'roles' => $user->roles->map(fn ($role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                    // 从语言文件获取角色显示名称，如果不存在则使用首字母大写的角色名
                    'display_name' => __('roles.'.$role->name.'.display_name', [], 'zh_CN') ?? ucfirst($role->name),
                    'is_system' => $role->name === 'admin',
                ])->toArray(),
            ]);

        // 渲染 Inertia 页面，传递用户列表和编辑权限状态
        return Inertia::render('Users/Index', [
            'users' => $users,
            'canEdit' => $canEdit,
        ]);
    }

    /**
     * 显示编辑用户角色的表单页面。
     *
     * 获取特定用户及其当前角色，并提供所有可选角色供选择。
     *
     * @param  User  $user  要编辑的用户实例
     */
    public function edit(User $user): InertiaResponse
    {
        // 授权检查：确保当前用户有权限更新目标用户
        $this->authorize('update', $user);

        // 获取所有可用的角色，只加载id和名称
        $allRoles = Role::select('id', 'name')->get();

        // 渲染 Inertia 页面，传递用户数据和所有可选角色
        return Inertia::render('Users/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                // 当前用户已拥有的角色ID列表
                'roles' => $user->roles()->pluck('id')->toArray(),
            ],
            // 传递所有可选的角色，并包含从语言文件获取的元数据
            'roles' => $allRoles->map(fn ($role) => [
                'id' => $role->id,
                'name' => $role->name,
                // 从语言文件获取角色显示名称
                'display_name' => __('roles.'.$role->name.'.display_name', [], 'zh_CN') ?? ucfirst($role->name),
                // 从语言文件获取角色描述
                'description' => __('roles.'.$role->name.'.description', [], 'zh_CN'),
                'is_system' => $role->name === 'admin',
            ])->toArray(),
        ]);
    }

    /**
     * 更新指定用户的角色。
     * 验证请求中的角色数据，处理特定业务逻辑（如阻止移除自身管理员角色），
     * 然后更新用户角色并记录操作日志。
     * @param  Request  $request  HTTP请求实例，包含待更新的角色ID
     * @param  User  $user  要更新角色的用户实例
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        // 授权检查：确保当前用户有权限更新目标用户
        $this->authorize('update', $user);
        // 验证请求数据，确保角色ID有效
        $validated = $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'exists:'.config('permission.table_names.roles').',id',
        ]);
        // 阻止用户移除自己的管理员角色
        if ($user->id === Auth::id() && $user->hasRole('admin')) {
            $adminRoleId = Role::where('name', 'admin')->value('id');
            if ($adminRoleId && ! in_array($adminRoleId, $validated['roles'])) {
                return back()->with('flash', ['message' => ['type' => 'error', 'text' => '不能移除自己的管理员角色。']]);
            }
        }
        try {
            // 同步用户角色
            $user->syncRoles($validated['roles']);
            // 记录操作日志
            $this->logActivity('update_roles', $user, ['roles' => Role::whereIn('id', $validated['roles'])->pluck('name')->toArray()]);
            // 重定向到用户列表页，并带上成功消息
            return redirect()->route('users.index')
                ->with('flash', ['message' => ['type' => 'success', 'text' => '用户角色更新成功！']]);
        } catch (\Exception $e) {
            // 捕获异常，记录错误日志并返回错误消息
            Log::error("Error updating user roles for user {$user->id}: ".$e->getMessage());
            return back()->with('flash', ['message' => ['type' => 'error', 'text' => '更新用户角色时出错。']]);
        }
    }
}

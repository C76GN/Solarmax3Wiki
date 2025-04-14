<?php

namespace App\Http\Controllers;

use App\Models\User; // 使用 App User 模型
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // 仍然需要 Auth::check()
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Spatie\Permission\Models\Role; // 使用 Spatie Role 模型

class UserController extends Controller // 确保继承了 Controller
{
    /**
     * 显示用户列表.
     */
    public function index(): InertiaResponse
    {
        // 使用 Policy 进行授权检查
        $this->authorize('viewAny', User::class);

        // 检查当前用户是否有编辑权限，用于控制前端按钮
        $canEdit = Auth::check() && Auth::user()->can('user.edit');

        $users = User::with('roles:id,name') // 只需加载 name
            ->latest()
            ->paginate(10)
            ->through(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at,
                'roles' => $user->roles->map(fn ($role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                    // 从语言文件获取显示名称
                    'display_name' => __('roles.'.$role->name.'.display_name', [], 'zh_CN') ?? ucfirst($role->name),
                    'is_system' => $role->name === 'admin',
                ])->toArray(),
            ]);

        return Inertia::render('Users/Index', [
            'users' => $users,
            'canEdit' => $canEdit, // 传递编辑权限状态给前端
        ]);
    }

    /**
     * 显示编辑用户角色的表单.
     */
    public function edit(User $user): InertiaResponse
    {
        // 使用 Policy 检查授权 (检查当前用户是否有权 update 目标 $user)
        $this->authorize('update', $user);

        // 获取所有 Spatie 角色，只需 id 和 name
        $allRoles = Role::select('id', 'name')->get();

        // 传递给前端
        return Inertia::render('Users/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles()->pluck('id')->toArray(), // 当前角色 ID
            ],
            // 传递所有可选的角色，并包含从语言文件获取的元数据
            'roles' => $allRoles->map(fn ($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'display_name' => __('roles.'.$role->name.'.display_name', [], 'zh_CN') ?? ucfirst($role->name),
                'description' => __('roles.'.$role->name.'.description', [], 'zh_CN'), // 可能为 null
                'is_system' => $role->name === 'admin',
            ])->toArray(),
        ]);
    }

    /**
     * 更新用户的角色.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        // 使用 Policy 检查授权
        $this->authorize('update', $user);

        $validated = $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'exists:'.config('permission.table_names.roles').',id',
        ]);

        // 阻止用户移除自己的管理员角色 (保持不变)
        if ($user->id === Auth::id() && $user->hasRole('admin')) {
            $adminRoleId = Role::where('name', 'admin')->value('id');
            if ($adminRoleId && ! in_array($adminRoleId, $validated['roles'])) {
                return back()->with('flash', ['message' => ['type' => 'error', 'text' => '不能移除自己的管理员角色。']]);
            }
        }

        try {
            $user->syncRoles($validated['roles']);

            // $this->logActivity('update_roles', $user, ['roles' => Role::whereIn('id', $validated['roles'])->pluck('name')->toArray()]);

            return redirect()->route('users.index')
                ->with('flash', ['message' => ['type' => 'success', 'text' => '用户角色更新成功！']]);
        } catch (\Exception $e) {
            Log::error("Error updating user roles for user {$user->id}: ".$e->getMessage());

            return back()->with('flash', ['message' => ['type' => 'error', 'text' => '更新用户角色时出错。']]);
        }
    }

    // 移除辅助方法 getRoleDisplayName 和 getRoleDescription
}

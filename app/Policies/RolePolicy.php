<?php

namespace App\Policies;

use App\Models\User; // 使用 App\Models\User
use Illuminate\Auth\Access\HandlesAuthorization; // 使用 Spatie\Models\Role
use Spatie\Permission\Models\Role;

class RolePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     * 对应 'role.view' 权限
     */
    public function viewAny(User $user): bool
    {
        return $user->can('role.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Role $role): bool
    {
        // 通常只要能看列表就能看单个详情
        return $user->can('role.view');
    }

    /**
     * Determine whether the user can create models.
     * 对应 'role.create' 权限
     */
    public function create(User $user): bool
    {
        return $user->can('role.create');
    }

    /**
     * Determine whether the user can update the model.
     * 对应 'role.edit' 权限
     */
    public function update(User $user, Role $role): bool
    {
        // 系统管理员角色通常不允许编辑
        if ($role->name === 'admin') {
            // 可以在这里决定是否完全禁止，或只允许特定用户（如 superadmin）
            return false; // 简单处理：禁止编辑 admin
        }

        return $user->can('role.edit');
    }

    /**
     * Determine whether the user can delete the model.
     * 对应 'role.delete' 权限
     */
    public function delete(User $user, Role $role): bool
    {
        // 系统管理员角色通常不允许删除
        if ($role->name === 'admin') {
            return false;
        }

        // 还可以检查角色是否仍被用户使用
        // if ($role->users()->exists()) {
        //     return false; // 可以在控制器或这里检查
        // }
        return $user->can('role.delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    // public function restore(User $user, Role $role): bool
    // {
    //     return $user->can('role.restore');
    // }

    /**
     * Determine whether the user can permanently delete the model.
     */
    // public function forceDelete(User $user, Role $role): bool
    // {
    //     return $user->can('role.force_delete');
    // }
}

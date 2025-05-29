<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Spatie\Permission\Models\Role;

/**
 * RolePolicy 类
 * 用于定义用户对 Role 模型的授权逻辑。
 */
class RolePolicy
{
    use HandlesAuthorization;

    /**
     * 判断用户是否可以查看所有角色。
     *
     * @param  \App\Models\User  $user 当前用户
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->can('role.view');
    }

    /**
     * 判断用户是否可以查看指定角色。
     *
     * @param  \App\Models\User  $user 当前用户
     * @param  \Spatie\Permission\Models\Role  $role 要查看的角色
     * @return bool
     */
    public function view(User $user, Role $role): bool
    {
        return $user->can('role.view');
    }

    /**
     * 判断用户是否可以创建角色。
     *
     * @param  \App\Models\User  $user 当前用户
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->can('role.create');
    }

    /**
     * 判断用户是否可以更新指定角色。
     *
     * @param  \App\Models\User  $user 当前用户
     * @param  \Spatie\Permission\Models\Role  $role 要更新的角色
     * @return bool
     */
    public function update(User $user, Role $role): bool
    {
        // 管理员角色（'admin'）不允许被修改
        if ($role->name === 'admin') {
            return false;
        }

        return $user->can('role.edit');
    }

    /**
     * 判断用户是否可以删除指定角色。
     *
     * @param  \App\Models\User  $user 当前用户
     * @param  \Spatie\Permission\Models\Role  $role 要删除的角色
     * @return bool
     */
    public function delete(User $user, Role $role): bool
    {
        // 管理员角色（'admin'）不允许被删除
        if ($role->name === 'admin') {
            return false;
        }

        return $user->can('role.delete');
    }
}
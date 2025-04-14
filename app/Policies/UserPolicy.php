<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization; // 确保引入

class UserPolicy
{
    use HandlesAuthorization; // 使用这个 Trait

    /**
     * Determine whether the user can view any models.
     * 对应 'user.view' 权限
     */
    public function viewAny(User $user): bool
    {
        return $user->can('user.view');
    }

    /**
     * Determine whether the user can view the model.
     * 查看单个用户的信息（如果需要）
     */
    public function view(User $currentUser, User $targetUser): bool
    {
        // 允许查看自己，或者有查看权限
        return $currentUser->id === $targetUser->id || $currentUser->can('user.view');
    }

    /**
     * Determine whether the user can create models.
     * 通常不允许通过 UI 创建用户（使用注册）
     */
    // public function create(User $user): bool
    // {
    //     return $user->can('user.create'); // 如果有这个权限的话
    // }

    /**
     * Determine whether the user can update the model's roles.
     * 对应 'user.edit' 权限
     * 注意：这里的 $targetUser 是被编辑的用户
     */
    public function update(User $currentUser, User $targetUser): bool
    {
        // 通常不允许用户修改自己的角色，除非是超级管理员等特殊情况
        // if ($currentUser->id === $targetUser->id) {
        //     return false; // 或者根据你的逻辑判断
        // }
        // 检查是否有编辑用户角色的权限
        return $currentUser->can('user.edit');
    }

    /**
     * Determine whether the user can delete the model.
     * 对应 'user.delete' 权限（如果需要）
     */
    // public function delete(User $currentUser, User $targetUser): bool
    // {
    //     // 防止删除自己，或者 admin 用户
    //     if ($currentUser->id === $targetUser->id || $targetUser->hasRole('admin')) {
    //         return false;
    //     }
    //     return $currentUser->can('user.delete');
    // }

    /**
     * Determine whether the user can restore the model.
     */
    // public function restore(User $user, User $targetUser): bool
    // {
    //     return $user->can('user.restore'); // 如果有软删除和恢复功能
    // }

    /**
     * Determine whether the user can permanently delete the model.
     */
    // public function forceDelete(User $user, User $targetUser): bool
    // {
    //     return $user->can('user.force_delete');
    // }
}

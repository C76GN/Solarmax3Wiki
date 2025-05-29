<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * 用户策略
 * 定义用户模型相关的授权逻辑。
 */
class UserPolicy
{
    use HandlesAuthorization;

    /**
     * 判断用户是否可以查看任何用户。
     *
     * @param  \App\Models\User  $user 当前认证用户。
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->can('user.view');
    }

    /**
     * 判断用户是否可以查看指定用户。
     *
     * @param  \App\Models\User  $currentUser 当前认证用户。
     * @param  \App\Models\User  $targetUser  被查看的用户。
     * @return bool
     */
    public function view(User $currentUser, User $targetUser): bool
    {
        // 允许用户查看自己的信息，或拥有 'user.view' 权限。
        return $currentUser->id === $targetUser->id || $currentUser->can('user.view');
    }

    /**
     * 判断用户是否可以更新指定用户的角色。
     *
     * @param  \App\Models\User  $currentUser 当前认证用户。
     * @param  \App\Models\User  $targetUser  被更新的用户。
     * @return bool
     */
    public function update(User $currentUser, User $targetUser): bool
    {
        // 拥有 'user.edit' 权限才能编辑用户角色。
        return $currentUser->can('user.edit');
    }
}
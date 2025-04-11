<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WikiComment;
use Illuminate\Auth\Access\HandlesAuthorization; // 或者 Gate

class WikiCommentPolicy
{
    use HandlesAuthorization; // 如果使用 HandlesAuthorization

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, WikiComment $wikiComment): bool
    {
        // 用户是评论所有者 或 用户有管理评论的权限
        return $user->id === $wikiComment->user_id || $user->hasPermission('wiki.moderate_comments');
    }

    /**
     * Determine whether the user can delete the model.
     * 在你的应用中，删除是隐藏，所以可以用 delete 权限控制隐藏操作
     */
    public function delete(User $user, WikiComment $wikiComment): bool
    {
        // 用户是评论所有者 或 用户有管理评论的权限
        return $user->id === $wikiComment->user_id || $user->hasPermission('wiki.moderate_comments');
    }

    /**
     * Determine whether the user can view the model. (如果需要)
     */
    // public function view(User $user, WikiComment $wikiComment): bool
    // {
    //     // 通常所有登录用户都能看，或者根据 is_hidden 判断
    //     return true;
    // }

    /**
     * Determine whether the user can create models. (如果需要，可以用在 store 方法前)
     */
    // public function create(User $user): bool
    // {
    //     return $user->hasPermission('wiki.comment');
    // }

    // ... 其他策略方法 ...
}

<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WikiComment;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * WikiCommentPolicy 类
 * 定义了 Wiki 评论相关的用户授权逻辑。
 */
class WikiCommentPolicy
{
    use HandlesAuthorization;

    /**
     * 判断用户是否可以更新指定的 Wiki 评论。
     *
     * @param  \App\Models\User  $user        当前认证用户。
     * @param  \App\Models\WikiComment  $wikiComment  要更新的 Wiki 评论。
     * @return bool
     */
    public function update(User $user, WikiComment $wikiComment): bool
    {
        // 只有评论的作者或拥有 'wiki.moderate_comments' 权限的用户才能更新评论。
        return $user->id === $wikiComment->user_id || $user->hasPermission('wiki.moderate_comments');
    }

    /**
     * 判断用户是否可以删除（隐藏）指定的 Wiki 评论。
     *
     * @param  \App\Models\User  $user        当前认证用户。
     * @param  \App\Models\WikiComment  $wikiComment  要删除的 Wiki 评论。
     * @return bool
     */
    public function delete(User $user, WikiComment $wikiComment): bool
    {
        // 只有评论的作者或拥有 'wiki.moderate_comments' 权限的用户才能删除（隐藏）评论。
        return $user->id === $wikiComment->user_id || $user->hasPermission('wiki.moderate_comments');
    }
}
<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WikiPage;
use Illuminate\Auth\Access\HandlesAuthorization; // 权限授权处理 Trait

/**
 * WikiPagePolicy 类
 * 定义了用户对 Wiki 页面的各种操作权限。
 */
class WikiPagePolicy
{
    use HandlesAuthorization; // 引入授权逻辑处理功能

    /**
     * 判断用户是否可以查看任何 Wiki 页面 (如 Wiki 列表页)。
     *
     * @param \App\Models\User|null $user 当前认证用户或 null。
     * @return bool
     */
    public function viewAny(?User $user): bool
    {
        // 允许所有用户（包括未登录用户）查看 Wiki 列表页。
        return true;
    }

    /**
     * 判断用户是否可以查看指定 Wiki 页面。
     *
     * @param \App\Models\User|null $user 当前认证用户或 null。
     * @param \App\Models\WikiPage $wikiPage 待查看的 Wiki 页面实例。
     * @return bool
     */
    public function view(?User $user, WikiPage $wikiPage): bool
    {
        // 已发布的页面允许所有用户查看。
        if ($wikiPage->status === WikiPage::STATUS_PUBLISHED) {
            return true;
        }

        // 对于非公开状态的页面（如草稿、冲突），需要用户登录并拥有 'wiki.view' 权限。
        return $user?->can('wiki.view') ?? false;
    }

    /**
     * 判断用户是否可以创建 Wiki 页面。
     *
     * @param \App\Models\User $user 当前认证用户。
     * @return bool
     */
    public function create(User $user): bool
    {
        // 用户需拥有 'wiki.create' 权限。
        return $user->can('wiki.create');
    }

    /**
     * 判断用户是否可以更新 Wiki 页面。
     *
     * @param \App\Models\User $user 当前认证用户。
     * @param \App\Models\WikiPage $wikiPage 待更新的 Wiki 页面实例。
     * @return bool
     */
    public function update(User $user, WikiPage $wikiPage): bool
    {
        // 如果页面处于冲突状态，只有拥有 'wiki.resolve_conflict' 权限的用户才能更新。
        if ($wikiPage->status === WikiPage::STATUS_CONFLICT) {
            return $user->can('wiki.resolve_conflict');
        }

        // 如果页面被锁定且锁定者不是当前用户，则不能更新。
        if ($wikiPage->isLocked() && $wikiPage->locked_by !== $user->id) {
            return false;
        }

        // 用户需拥有 'wiki.edit' 权限。
        return $user->can('wiki.edit');
    }

    /**
     * 判断用户是否可以删除 Wiki 页面 (软删除)。
     *
     * @param \App\Models\User $user 当前认证用户。
     * @param \App\Models\WikiPage $wikiPage 待删除的 Wiki 页面实例。
     * @return bool
     */
    public function delete(User $user, WikiPage $wikiPage): bool
    {
        // 用户需拥有 'wiki.delete' 权限。
        return $user->can('wiki.delete');
    }

    /**
     * 判断用户是否可以查看 Wiki 回收站。
     *
     * @param \App\Models\User $user 当前认证用户。
     * @return bool
     */
    public function viewTrash(User $user): bool
    {
        // 用户需拥有 'wiki.trash.view' 权限。
        return $user->can('wiki.trash.view');
    }

    /**
     * 判断用户是否可以恢复回收站中的 Wiki 页面。
     *
     * @param \App\Models\User $user 当前认证用户。
     * @param \App\Models\WikiPage|null $wikiPage 待恢复的 Wiki 页面实例 (可选，可用于检查特定页面权限)。
     * @return bool
     */
    public function restore(User $user, ?WikiPage $wikiPage = null): bool
    {
        // 用户需拥有 'wiki.trash.restore' 权限。
        return $user->can('wiki.trash.restore');
    }

    /**
     * 判断用户是否可以永久删除回收站中的 Wiki 页面。
     *
     * @param \App\Models\User $user 当前认证用户。
     * @param \App\Models\WikiPage|null $wikiPage 待永久删除的 Wiki 页面实例 (可选)。
     * @return bool
     */
    public function forceDelete(User $user, ?WikiPage $wikiPage = null): bool
    {
        // 用户需拥有 'wiki.trash.force_delete' 权限。
        return $user->can('wiki.trash.force_delete');
    }

    /**
     * 判断用户是否可以解决 Wiki 页面冲突。
     *
     * @param \App\Models\User $user 当前认证用户。
     * @param \App\Models\WikiPage $wikiPage 存在冲突的 Wiki 页面实例。
     * @return bool
     */
    public function resolveConflict(User $user, WikiPage $wikiPage): bool
    {
        // 用户需拥有 'wiki.resolve_conflict' 权限。
        return $user->can('wiki.resolve_conflict');
    }

    /**
     * 判断用户是否可以查看 Wiki 页面历史版本。
     *
     * @param \App\Models\User $user 当前认证用户。
     * @param \App\Models\WikiPage $wikiPage 待查看历史的 Wiki 页面实例。
     * @return bool
     */
    public function viewHistory(User $user, WikiPage $wikiPage): bool
    {
        // 用户需拥有 'wiki.history' 权限。
        return $user->can('wiki.history');
    }

    /**
     * 判断用户是否可以将 Wiki 页面恢复到历史版本。
     *
     * @param \App\Models\User $user 当前认证用户。
     * @param \App\Models\WikiPage $wikiPage 待恢复的 Wiki 页面实例。
     * @return bool
     */
    public function revert(User $user, WikiPage $wikiPage): bool
    {
        // 页面不能处于冲突状态，并且用户需拥有 'wiki.edit' 权限。
        return $wikiPage->status !== WikiPage::STATUS_CONFLICT && $user->can('wiki.edit');
    }
}
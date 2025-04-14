<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WikiPage;
use Illuminate\Auth\Access\HandlesAuthorization; // 引入 HandlesAuthorization

class WikiPagePolicy
{
    use HandlesAuthorization; // 使用 Trait

    /**
     * Determine whether the user can view any models (Wiki Index page).
     */
    public function viewAny(?User $user): bool
    {
        // 通常所有人都可以查看 Wiki 索引页
        return true; // 或者根据需要检查 'wiki.view'
        // return $user?->can('wiki.view') ?? true; // 如果未登录用户也可以看
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, WikiPage $wikiPage): bool
    {
        // 公开页面允许所有人看
        if ($wikiPage->status === WikiPage::STATUS_PUBLISHED) {
            return true;
        }
        // 草稿、冲突等状态需要登录并有查看权限
        // 这里简化处理，假设有 'wiki.view' 权限就能看所有状态
        // 注意：如果 $user 为 null (未登录), $user?->can 会返回 null，你需要处理这种情况
        return $user?->can('wiki.view') ?? false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('wiki.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, WikiPage $wikiPage): bool
    {
        // 处于冲突状态时，只有能解决冲突的人可以“更新”（通过 resolveConflict 流程）
        if ($wikiPage->status === WikiPage::STATUS_CONFLICT) {
            return $user->can('wiki.resolve_conflict');
        }
        // 检查页面是否被锁定，并且不是自己锁定的
        if ($wikiPage->isLocked() && $wikiPage->locked_by !== $user->id) {
            return false;
        }
        // 检查是否有编辑权限
        return $user->can('wiki.edit');
    }

    /**
     * Determine whether the user can delete the model (soft delete).
     */
    public function delete(User $user, WikiPage $wikiPage): bool
    {
        // 可以在这里添加其他逻辑，比如只有作者或管理员能删除
        return $user->can('wiki.delete');
    }

    /**
     * Determine whether the user can view the trash bin.
     */
    public function viewTrash(User $user): bool // 注意：第一个参数是 $user，Policy 规定
    {
        return $user->can('wiki.trash.view');
    }

    /**
     * Determine whether the user can restore a wiki page.
     */
    public function restore(User $user, ?WikiPage $wikiPage = null): bool // 可以处理类名或实例
    {
        return $user->can('wiki.trash.restore');
    }

    /**
     * Determine whether the user can permanently delete a wiki page.
     */
    public function forceDelete(User $user, ?WikiPage $wikiPage = null): bool
    {
        return $user->can('wiki.trash.force_delete');
    }

    /**
     * Determine whether the user can resolve conflicts for the model.
     */
    public function resolveConflict(User $user, WikiPage $wikiPage): bool
    {
        return $user->can('wiki.resolve_conflict');
    }

    /**
     * Determine whether the user can view the history for the model.
     */
    public function viewHistory(User $user, WikiPage $wikiPage): bool
    {
        // 假设有查看权限就可以看历史
        return $user->can('wiki.history');
    }

    /**
     * Determine whether the user can revert the model to a previous version.
     */
    public function revert(User $user, WikiPage $wikiPage): bool
    {
        // 恢复版本本质上是创建新版本，所以检查编辑权限
        // 并且页面不能处于冲突状态
        return $wikiPage->status !== WikiPage::STATUS_CONFLICT && $user->can('wiki.edit');
    }

    // --- WikiComment 相关权限如果放在这里 ---
    // （你也可以保持在 WikiCommentPolicy 中）

    // public function viewComments(User $user, WikiPage $wikiPage): bool
    // {
    //     // 假设有查看权限就能看评论
    //     return $user->can('wiki.view');
    // }

    // public function createComment(User $user, WikiPage $wikiPage): bool
    // {
    //     return $user->can('wiki.comment');
    // }
}

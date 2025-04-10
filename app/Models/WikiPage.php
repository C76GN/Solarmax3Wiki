<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;

class WikiPage extends Model
{
    use HasFactory, LogsActivity, Notifiable;

    protected $fillable = [
        'title',
        'slug',
        'status',
        'created_by',
        'current_version_id',
        'is_locked',
        'locked_by',
        'locked_until',
    ];

    protected $casts = [
        'is_locked' => 'boolean',
        'locked_until' => 'datetime',
    ];

    // 页面状态常量
    const STATUS_DRAFT = 'draft';

    const STATUS_PUBLISHED = 'published';

    const STATUS_CONFLICT = 'conflict';

    /**
     * 获取当前页面的版本
     */
    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(WikiVersion::class, 'current_version_id');
    }

    /**
     * 获取页面的所有版本
     */
    public function versions(): HasMany
    {
        return $this->hasMany(WikiVersion::class);
    }

    /**
     * 获取页面的创建者
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * 获取锁定页面的用户
     */
    public function locker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    /**
     * 获取页面所属的分类
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(WikiCategory::class, 'wiki_page_category');
    }

    /**
     * 获取页面的标签
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(WikiTag::class, 'wiki_page_tag');
    }

    /**
     * 获取页面的顶级评论 (未隐藏)
     */
    public function comments(): HasMany
    {
        return $this->hasMany(WikiComment::class)
            ->whereNull('parent_id')
            ->where('is_hidden', false); // 添加条件只获取未隐藏的顶级评论
    }

    /**
     * 获取页面的草稿
     */
    public function drafts(): HasMany
    {
        return $this->hasMany(WikiPageDraft::class);
    }

    // 移除 parent() 和 children() 关系
    // /**
    //  * Get the parent page.
    //  */
    // public function parent(): BelongsTo
    // {
    //     return $this->belongsTo(WikiPage::class, 'parent_id');
    // }
    //
    // /**
    //  * Get the child pages.
    //  */
    // public function children(): HasMany
    // {
    //     return $this->hasMany(WikiPage::class, 'parent_id')->orderBy('order');
    // }

    /**
     * 检查页面是否被锁定且未过期
     */
    public function isLocked(): bool
    {
        return $this->is_locked && $this->locked_until && $this->locked_until->isFuture();
    }

    public function isLockedDueToConflict(): bool
    {
        // 简单的实现可以直接检查 status
        return $this->status === self::STATUS_CONFLICT;
        // 或者，如果保留 is_locked 字段并与 conflict 同步：
        // return $this->is_locked && $this->status === self::STATUS_CONFLICT;
    }

    /**
     * 将页面标记为冲突状态
     */
    public function markAsConflict(): bool
    {
        $previousStatus = $this->status;
        // 进入冲突状态时，可以设置 is_locked = true，locked_until=null
        $updated = $this->update([
            'status' => self::STATUS_CONFLICT,
            'is_locked' => true, // 标记为锁定状态，表示需要解决冲突
            'locked_by' => null, // 可以为空，或指向一个系统标记
            'locked_until' => null, // 冲突锁定没有时间限制
        ]);
        if ($updated) {
            Log::warning("Page {$this->id} ('{$this->title}') marked as CONFLICT. Previous status: {$previousStatus}");
            $this->logCustomActivity('conflict_detected', ['previous_status' => $previousStatus]);
            // 更新模型状态
            $this->status = self::STATUS_CONFLICT;
            $this->is_locked = true;
            $this->locked_by = null;
            $this->locked_until = null;
        } else {
            Log::error("Failed to mark page {$this->id} as CONFLICT in the database.");
        }

        return $updated;
    }

    /**
     * 将页面标记为已解决冲突状态
     */
    public function markAsResolved(User $resolver): bool
    {
        if ($this->status !== self::STATUS_CONFLICT) {
            Log::warning("Attempted to mark page {$this->id} as resolved, but it was not in conflict status.");

            return true; // 不是冲突状态，认为操作"成功"（无事发生）
        }

        // 解决冲突后，状态变为 published，解除锁定标记
        $updated = $this->update([
            'status' => self::STATUS_PUBLISHED,
            'is_locked' => false,
            'locked_by' => null,
            'locked_until' => null,
        ]);
        if ($updated) {
            Log::info("Page {$this->id} ('{$this->title}') conflict marked as RESOLVED by user {$resolver->id}.");
            $this->logCustomActivity('conflict_resolved', ['resolved_by' => $resolver->id]);
            // 更新模型状态
            $this->status = self::STATUS_PUBLISHED;
            $this->is_locked = false;
            $this->locked_by = null;
            $this->locked_until = null;
        } else {
            Log::error("Failed to mark page {$this->id} as RESOLVED in the database.");
        }

        return $updated;
    }
}

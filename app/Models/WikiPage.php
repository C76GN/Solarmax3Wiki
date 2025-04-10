<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\LogsActivity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class WikiPage extends Model
{
    use HasFactory, Notifiable, LogsActivity;

    const DEFAULT_LOCK_DURATION_MINUTES = 5; // 定义锁的默认持续时间（分钟）

    protected $fillable = [
        'title',
        'slug',
        'status',
        'created_by',
        'current_version_id',
        'is_locked',
        'locked_by',
        'locked_until',
        // 移除 parent_id 和 order
        // 'parent_id',
        // 'order',
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

    /**
     * 检查页面是否被其他用户锁定
     */
     public function isLockedByAnotherUser(): bool
     {
        $currentUser = Auth::user();
        // 页面已锁定，存在当前用户，且锁定者不是当前用户
        return $this->isLocked() && $currentUser && $this->locked_by !== $currentUser->id;
     }

    /**
     * 锁定页面
     */
    public function lock(User $user, int $minutes = self::DEFAULT_LOCK_DURATION_MINUTES): void
    {
        $now = now();
        $expiresAt = $now->addMinutes($minutes);
        $this->update([
            'is_locked' => true,
            'locked_by' => $user->id,
            'locked_until' => $expiresAt
        ]);
         Log::info("Page {$this->id} ('{$this->title}') locked by user {$user->id} ({$user->name}) until {$expiresAt}");
    }

    /**
     * 解锁页面
     */
    public function unlock(): void
    {
        if ($this->is_locked || $this->locked_by) {
            $previouslyLockedBy = $this->locked_by;
            $previouslyLockedUntil = $this->locked_until ? $this->locked_until->toDateTimeString() : 'N/A';

            $this->update([
                'is_locked' => false,
                'locked_by' => null,
                'locked_until' => null
            ]);

            Log::info("Page {$this->id} ('{$this->title}') unlocked. Was locked by: {$previouslyLockedBy} until {$previouslyLockedUntil}.");
        } else {
            // Log::debug("Page {$this->id} ('{$this->title}') was already unlocked.");
        }
    }

     /**
     * 刷新页面锁定的时间
     */
     public function refreshLock(int $minutes = self::DEFAULT_LOCK_DURATION_MINUTES): bool
     {
        if ($this->isLocked()) {
            $newExpiry = now()->addMinutes($minutes);
            $this->update([
                'locked_until' => $newExpiry
            ]);
            // Log::info("Lock refreshed for Page {$this->id} ('{$this->title}') until {$newExpiry}");
            return true;
        }
         Log::warning("Attempted to refresh lock for Page {$this->id} ('{$this->title}'), but it was not locked or lock expired.");
        return false;
     }

    /**
     * 将页面标记为冲突状态
     */
    public function markAsConflict(): void
    {
        $previousStatus = $this->status;
        $this->update([
            'status' => self::STATUS_CONFLICT,
            // 冲突时通常也需要解除编辑锁，允许有权限者解决
            'is_locked' => true, // 或者 false，取决于业务逻辑，这里先保持锁定，解决者需要有解锁权限或逻辑
            'locked_by' => null, // 清除锁定者，因为现在是冲突状态
            'locked_until' => null // 清除锁定时间
        ]);
         Log::warning("Page {$this->id} ('{$this->title}') marked as CONFLICT. Previous status: {$previousStatus}");
         // 记录冲突日志
         $this->logCustomActivity('conflict_detected', ['previous_status' => $previousStatus]);
    }

     /**
      * 将页面标记为已解决冲突状态
      */
     public function markAsResolved(User $resolver): void
     {
        $this->update([
            'status' => self::STATUS_PUBLISHED, // 恢复为发布状态
            'is_locked' => false,              // 解除锁定
            'locked_by' => null,
            'locked_until' => null
        ]);
         Log::info("Page {$this->id} ('{$this->title}') conflict marked as RESOLVED by user {$resolver->id}.");
         // 记录冲突解决日志
         $this->logCustomActivity('conflict_resolved', ['resolved_by' => $resolver->id]);
     }
}
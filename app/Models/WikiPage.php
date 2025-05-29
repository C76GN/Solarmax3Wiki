<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;

/**
 * WikiPage 模型
 * 表示 Wiki 页面，包含其内容、版本、状态及相关信息。
 */
class WikiPage extends Model
{
    use HasFactory, LogsActivity, Notifiable, SoftDeletes;

    /**
     * 可批量赋值的属性。
     *
     * @var array
     */
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

    /**
     * 属性类型转换。
     *
     * @var array
     */
    protected $casts = [
        'is_locked' => 'boolean',
        'locked_until' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * 页面状态：草稿。
     */
    const STATUS_DRAFT = 'draft';

    /**
     * 页面状态：已发布。
     */
    const STATUS_PUBLISHED = 'published';

    /**
     * 页面状态：冲突。
     */
    const STATUS_CONFLICT = 'conflict';

    /**
     * 获取页面当前版本。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(WikiVersion::class, 'current_version_id');
    }

    /**
     * 获取页面的所有历史版本。
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function versions(): HasMany
    {
        return $this->hasMany(WikiVersion::class);
    }

    /**
     * 获取页面的创建者。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * 获取锁定页面的用户。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function locker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    /**
     * 获取页面所属的分类。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(WikiCategory::class, 'wiki_page_category');
    }

    /**
     * 获取页面关联的标签。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(WikiTag::class, 'wiki_page_tag');
    }

    /**
     * 获取页面的顶级评论（不包括回复）。
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function comments(): HasMany
    {
        return $this->hasMany(WikiComment::class)
            ->whereNull('parent_id')
            ->where('is_hidden', false);
    }

    /**
     * 获取页面的草稿。
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function drafts(): HasMany
    {
        return $this->hasMany(WikiPageDraft::class);
    }

    /**
     * 检查页面是否被锁定。
     *
     * @return bool
     */
    public function isLocked(): bool
    {
        return $this->is_locked && $this->locked_until && $this->locked_until->isFuture();
    }

    /**
     * 检查页面是否因冲突而锁定。
     *
     * @return bool
     */
    public function isLockedDueToConflict(): bool
    {
        return $this->status === self::STATUS_CONFLICT;
    }

    /**
     * 将页面标记为冲突状态。
     *
     * @return bool
     */
    public function markAsConflict(): bool
    {
        $previousStatus = $this->status;
        $updated = $this->update([
            'status' => self::STATUS_CONFLICT,
            'is_locked' => true,
            'locked_by' => null,
            'locked_until' => null,
        ]);

        if ($updated) {
            Log::warning("Page {$this->id} ('{$this->title}') marked as CONFLICT. Previous status: {$previousStatus}");
            // 使用 trait 中的方法记录活动，如果存在
            if (method_exists($this, 'logCustomActivity')) {
                $this->logCustomActivity('conflict_detected', ['previous_status' => $previousStatus]);
            } else {
                // 否则，直接使用 ActivityLog 模型（如果需要）
                ActivityLog::log('conflict_detected', $this, ['previous_status' => $previousStatus]);
            }
            // 手动更新模型实例的状态（如果需要立即反映）
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
     * 将页面标记为已解决状态。
     *
     * @param \App\Models\User $resolver 解决冲突的用户实例。
     * @return bool
     */
    public function markAsResolved(User $resolver): bool
    {
        if ($this->status !== self::STATUS_CONFLICT) {
            Log::warning("Attempted to mark page {$this->id} as resolved, but it was not in conflict status.");

            return true;
        }

        $updated = $this->update([
            'status' => self::STATUS_PUBLISHED,
            'is_locked' => false,
            'locked_by' => null,
            'locked_until' => null,
        ]);

        if ($updated) {
            Log::info("Page {$this->id} ('{$this->title}') conflict marked as RESOLVED by user {$resolver->id}.");
            // 使用 trait 中的方法记录活动
            if (method_exists($this, 'logCustomActivity')) {
                $this->logCustomActivity('conflict_resolved', ['resolved_by' => $resolver->id]);
            } else {
                ActivityLog::log('conflict_resolved', $this, ['resolved_by' => $resolver->id]);
            }
            // 手动更新模型实例的状态
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
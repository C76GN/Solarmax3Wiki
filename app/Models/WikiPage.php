<?php

namespace App\Models;

// 引入 SoftDeletes Trait
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;

class WikiPage extends Model
{
    // 使用 SoftDeletes Trait
    use HasFactory, LogsActivity, Notifiable, SoftDeletes; // 添加 SoftDeletes

    protected $fillable = [
        'title',
        'slug',
        'status',
        'created_by',
        'current_version_id',
        'is_locked',
        'locked_by',
        'locked_until',
        // deleted_at 不需要加到 fillable
    ];

    protected $casts = [
        'is_locked' => 'boolean',
        'locked_until' => 'datetime',
        'deleted_at' => 'datetime', // 可选，通常 SoftDeletes 会自动处理
    ];

    const STATUS_DRAFT = 'draft';

    const STATUS_PUBLISHED = 'published';

    const STATUS_CONFLICT = 'conflict';

    // --- 其他方法保持不变 ---

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(WikiVersion::class, 'current_version_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(WikiVersion::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function locker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(WikiCategory::class, 'wiki_page_category');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(WikiTag::class, 'wiki_page_tag');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(WikiComment::class)
            ->whereNull('parent_id')
            ->where('is_hidden', false);
    }

    public function drafts(): HasMany
    {
        return $this->hasMany(WikiPageDraft::class);
    }

    public function isLocked(): bool
    {
        return $this->is_locked && $this->locked_until && $this->locked_until->isFuture();
    }

    public function isLockedDueToConflict(): bool
    {
        return $this->status === self::STATUS_CONFLICT;
    }

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

    public function markAsResolved(User $resolver): bool
    {
        if ($this->status !== self::STATUS_CONFLICT) {
            Log::warning("Attempted to mark page {$this->id} as resolved, but it was not in conflict status.");

            return true; // 或者 false，取决于你希望如何处理
        }

        $updated = $this->update([
            'status' => self::STATUS_PUBLISHED, // 解决后通常恢复为已发布
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

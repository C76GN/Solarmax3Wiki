<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\LogsActivity;

class WikiPage extends Model
{
    use LogsActivity;
    
    protected $fillable = [
        'title',
        'slug',
        'status',
        'template_id',
        'created_by',
        'current_version_id',
        'is_locked',
        'locked_by',
        'locked_until',
        'parent_id',
        'order',
        'meta'
    ];
    
    protected $casts = [
        'is_locked' => 'boolean',
        'locked_until' => 'datetime',
        'meta' => 'array'
    ];
    
    // 状态常量
    const STATUS_DRAFT = 'draft';
    const STATUS_PUBLISHED = 'published';
    const STATUS_CONFLICT = 'conflict';
    
    // 关联当前版本
    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(WikiVersion::class, 'current_version_id');
    }
    
    // 关联所有版本
    public function versions(): HasMany
    {
        return $this->hasMany(WikiVersion::class);
    }
    
    // 关联创建者
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    // 关联锁定者
    public function locker()
    {
        return $this->belongsTo(User::class, 'locked_by');
    }
    
    // 关联分类
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(WikiCategory::class, 'wiki_page_category');
    }
    
    // 关联标签
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(WikiTag::class, 'wiki_page_tag');
    }
    
    // 关联评论
    public function comments(): HasMany
    {
        return $this->hasMany(WikiComment::class);
    }
    
    // 关联草稿
    public function drafts(): HasMany
    {
        return $this->hasMany(WikiPageDraft::class);
    }
    
    // 关联父页面
    public function parent(): BelongsTo
    {
        return $this->belongsTo(WikiPage::class, 'parent_id');
    }
    
    // 关联子页面
    public function children(): HasMany
    {
        return $this->hasMany(WikiPage::class, 'parent_id')->orderBy('order');
    }
    
    // 关联模板
    public function template(): BelongsTo
    {
        return $this->belongsTo(WikiTemplate::class, 'template_id');
    }
    
    // 检查页面是否被锁定
    public function isLocked(): bool
    {
        return $this->is_locked && $this->locked_until && $this->locked_until > now();
    }
    
    // 锁定页面
    public function lock(User $user, int $minutes = 30): void
    {
        $this->update([
            'is_locked' => true,
            'locked_by' => $user->id,
            'locked_until' => now()->addMinutes($minutes)
        ]);
        
        // 记录锁定活动
        ActivityLog::log('lock', $this, [
            'locked_by' => $user->id,
            'expires_at' => now()->addMinutes($minutes)->toDateTimeString()
        ]);
    }
    
    // 解锁页面
    public function unlock(): void
    {
        // 记录解锁活动前获取锁定者信息
        $lockedBy = $this->locked_by;
        
        $this->update([
            'is_locked' => false,
            'locked_by' => null,
            'locked_until' => null
        ]);
        
        // 如果之前是锁定状态，记录解锁活动
        if ($lockedBy) {
            ActivityLog::log('unlock', $this, [
                'unlocked_by' => auth()->id() ?? null,
                'previously_locked_by' => $lockedBy
            ]);
        }
    }

    public function refreshLock(int $minutes = 30): void
    {
        if ($this->is_locked && $this->locked_by) {
            $this->update([
                'locked_until' => now()->addMinutes($minutes)
            ]);
        }
    }
    
    public function tryLock(User $user): bool
    {
        // 如果页面未锁定或锁已过期
        if (!$this->isLocked()) {
            $this->lock($user);
            return true;
        }
        
        // 如果是当前用户已锁定
        if ($this->locked_by === $user->id) {
            $this->refreshLock();
            return true;
        }
        
        return false;
    }

    // 更新页面状态为冲突
    public function markAsConflict(): void
    {
        $this->update([
            'status' => self::STATUS_CONFLICT,
            'is_locked' => true,
            'locked_by' => null,
            'locked_until' => null
        ]);
        
        // 记录冲突日志
        ActivityLog::log('conflict_detected', $this, [
            'previous_status' => $this->getOriginal('status')
        ]);
    }
    
    // 解决冲突
    public function resolveConflict(): void
    {
        $this->update([
            'status' => self::STATUS_PUBLISHED,
            'is_locked' => false,
            'locked_by' => null,
            'locked_until' => null
        ]);
        
        // 记录冲突解决日志
        ActivityLog::log('conflict_resolved', $this, [
            'resolved_by' => auth()->id()
        ]);
    }
}
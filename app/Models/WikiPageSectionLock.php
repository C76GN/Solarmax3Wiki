<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WikiPageSectionLock extends Model
{
    use HasFactory;

    protected $fillable = [
        'wiki_page_id',
        'user_id',
        'section_id',
        'section_title',
        'expires_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * 获取关联的页面
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(WikiPage::class, 'wiki_page_id');
    }

    /**
     * 获取锁定区域的用户
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * 检查锁是否已过期
     */
    public function isExpired(): bool
    {
        return now()->isAfter($this->expires_at);
    }
}
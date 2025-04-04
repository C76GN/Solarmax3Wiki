<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WikiPageDraft extends Model
{
    use HasFactory;

    protected $fillable = [
        'wiki_page_id',
        'user_id',
        'title',
        'content',
        'categories'
    ];

    protected $casts = [
        'categories' => 'array',
    ];

    /**
     * 获取关联的页面
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(WikiPage::class, 'wiki_page_id');
    }

    /**
     * 获取草稿的用户
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
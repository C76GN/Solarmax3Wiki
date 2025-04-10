<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WikiPageDraft extends Model
{
    protected $fillable = [
        'wiki_page_id',
        'user_id',
        'content',
        'last_saved_at',
    ];

    protected $casts = [
        'last_saved_at' => 'datetime',
    ];

    // 关联页面
    public function page(): BelongsTo
    {
        return $this->belongsTo(WikiPage::class, 'wiki_page_id');
    }

    // 关联用户
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

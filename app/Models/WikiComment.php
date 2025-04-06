<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\LogsActivity;

class WikiComment extends Model
{
    use LogsActivity;
    
    protected $fillable = [
        'wiki_page_id',
        'user_id',
        'parent_id',
        'content',
        'is_hidden'
    ];
    
    protected $casts = [
        'is_hidden' => 'boolean'
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
    
    // 关联父评论
    public function parent(): BelongsTo
    {
        return $this->belongsTo(WikiComment::class, 'parent_id');
    }
    
    // 关联子评论
    public function replies(): HasMany
    {
        return $this->hasMany(WikiComment::class, 'parent_id');
    }
}
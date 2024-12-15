<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class WikiArticle extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'status',
        'created_by',
        'last_edited_by',
        'published_at',
        'view_count'
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'view_count' => 'integer'
    ];

    // 关联到创建者
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // 关联到最后编辑者
    public function lastEditor()
    {
        return $this->belongsTo(User::class, 'last_edited_by');
    }

    // 范围查询：已发布的文章
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
                    ->whereNotNull('published_at');
    }

    // 增加浏览次数
    public function incrementViewCount()
    {
        $this->increment('view_count');
    }

    public function categories()
    {
        return $this->belongsToMany(WikiCategory::class, 'wiki_article_category');
    }
}
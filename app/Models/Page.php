<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Traits\LogsActivity;
use Illuminate\Notifications\Notifiable;
use Laravel\Scout\Searchable;

class Page extends Model
{
    use HasFactory, SoftDeletes, LogsActivity, Notifiable, Searchable;

    protected $fillable = [
        'template_id',
        'title',
        'slug',
        'content',
        'is_published',
        'status',
        'published_at',
        'last_edited_at',
        'last_edited_by',
        'view_count'
    ];

    protected $casts = [
        'content' => 'array',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'last_edited_at' => 'datetime',
        'deleted_at' => 'datetime',
        'view_count' => 'integer'
    ];

    // Scout 搜索配置
    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => json_encode($this->content),
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($page) {
            if (empty($page->slug)) {
                $page->slug = Str::slug($page->title);
            }
            
            $page->last_edited_by = auth()->id();
            $page->last_edited_at = now();
        });

        static::updating(function ($page) {
            $page->last_edited_by = auth()->id();
            $page->last_edited_at = now();
        });
    }

    // 关联方法
    public function template()
    {
        return $this->belongsTo(Template::class);
    }

    public function lastEditor()
    {
        return $this->belongsTo(User::class, 'last_edited_by');
    }

    // Scope 方法用于筛选
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeArchived($query)
    {
        return $query->where('status', 'archived');
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('title', 'like', '%'.$search.'%')
                    ->orWhere('slug', 'like', '%'.$search.'%');
            });
        });

        $query->when($filters['status'] ?? null, function ($query, $status) {
            $query->where('status', $status);
        });

        $query->when($filters['template_id'] ?? null, function ($query, $template_id) {
            $query->where('template_id', $template_id);
        });

        $query->when($filters['from_date'] ?? null, function ($query, $date) {
            $query->where('created_at', '>=', $date);
        });

        $query->when($filters['to_date'] ?? null, function ($query, $date) {
            $query->where('created_at', '<=', $date);
        });

        return $query;
    }

    public function getContent($field = null)
    {
        if ($field) {
            return $this->content[$field] ?? null;
        }
        return $this->content;
    }

    public function setContent($field, $value)
    {
        $content = $this->content ?? [];
        $content[$field] = $value;
        $this->content = $content;
    }

    public function incrementViewCount()
    {
        $this->increment('view_count');
    }
}
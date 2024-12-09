<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Page extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'template_id',
        'title',
        'slug',
        'content',
        'is_published',
        'published_at'
    ];

    protected $casts = [
        'content' => 'array',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($page) {
            if (empty($page->slug)) {
                $page->slug = Str::slug($page->title);
            }
        });
    }

    public function template()
    {
        return $this->belongsTo(Template::class);
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
}
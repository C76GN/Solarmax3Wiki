<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Template extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'fields',
        'is_system'
    ];

    protected $casts = [
        'fields' => 'array',
        'is_system' => 'boolean',
        'deleted_at' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($template) {
            if (empty($template->slug)) {
                $template->slug = Str::slug($template->name);
            }
        });
    }

    public function pages()
    {
        return $this->hasMany(Page::class);
    }

    public function getFields(): array
    {
        return $this->fields ?? [];
    }
}
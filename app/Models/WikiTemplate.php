<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\LogsActivity;

class WikiTemplate extends Model
{
    use LogsActivity;
    
    protected $fillable = [
        'name',
        'slug',
        'description',
        'structure',
        'created_by'
    ];
    
    protected $casts = [
        'structure' => 'array'
    ];
    
    // 关联页面
    public function pages(): HasMany
    {
        return $this->hasMany(WikiPage::class, 'template_id');
    }
    
    // 关联创建者
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
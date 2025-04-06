<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\LogsActivity;

class WikiCategory extends Model
{
    use LogsActivity;
    
    protected $fillable = [
        'name',
        'slug',
        'description',
        'parent_id',
        'order'
    ];
    
    // 关联页面
    public function pages(): BelongsToMany
    {
        return $this->belongsToMany(WikiPage::class, 'wiki_page_category');
    }
    
    // 关联父分类
    public function parent(): BelongsTo
    {
        return $this->belongsTo(WikiCategory::class, 'parent_id');
    }
    
    // 关联子分类
    public function children(): HasMany
    {
        return $this->hasMany(WikiCategory::class, 'parent_id')->orderBy('order');
    }
}
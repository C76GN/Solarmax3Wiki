<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * WikiCategory 模型
 * 表示 Wiki 页面分类。
 */
class WikiCategory extends Model
{
    use LogsActivity;

    /**
     * 可批量赋值的属性。
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'parent_id',
        'order',
    ];

    /**
     * 获取此分类下的 Wiki 页面。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function pages(): BelongsToMany
    {
        return $this->belongsToMany(WikiPage::class, 'wiki_page_category');
    }

    /**
     * 获取此分类的父分类。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(WikiCategory::class, 'parent_id');
    }

    /**
     * 获取此分类的所有子分类。
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function children(): HasMany
    {
        return $this->hasMany(WikiCategory::class, 'parent_id')->orderBy('order');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use App\Traits\LogsActivity;

/**
 * Wiki分类模型
 * 
 * 表示Wiki系统中的分类结构，支持层级分类（父子关系）
 * 可以与多个Wiki页面关联，并支持软删除和活动日志记录
 */
class WikiCategory extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    /**
     * 可批量赋值的属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',        // 分类名称
        'slug',        // URL友好的分类标识
        'description', // 分类描述
        'parent_id',   // 父分类ID
        'order'        // 分类排序顺序
    ];

    /**
     * 获取当前分类的父分类
     *
     * @return BelongsTo 父分类关联
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(WikiCategory::class, 'parent_id');
    }

    /**
     * 获取当前分类的子分类
     *
     * @return HasMany 子分类关联
     */
    public function children(): HasMany
    {
        return $this->hasMany(WikiCategory::class, 'parent_id');
    }

    /**
     * 获取属于该分类的页面
     *
     * @return BelongsToMany 页面关联
     */
    public function pages(): BelongsToMany
    {
        return $this->belongsToMany(WikiPage::class, 'wiki_page_category');
    }

    /**
     * 获取从根分类到当前分类的完整路径
     * 
     * 返回一个包含所有父分类和当前分类的集合，从根分类开始
     *
     * @return Collection 分类路径集合
     */
    public function getPathAttribute(): Collection
    {
        // 初始化路径集合，包含当前分类
        $path = collect([$this]);
        
        // 获取父分类
        $parent = $this->parent;
        
        // 循环向上获取所有父分类，并添加到路径前面
        while ($parent) {
            $path->prepend($parent);
            $parent = $parent->parent;
        }
        
        return $path;
    }

    /**
     * 获取当前分类的所有后代分类
     * 
     * 递归收集所有子分类和子分类的子分类，以此类推
     *
     * @return Collection 后代分类集合
     */
    public function descendants(): Collection
    {
        $descendants = collect();
        
        // 遍历所有直接子分类
        foreach ($this->children as $child) {
            // 添加子分类本身
            $descendants->push($child);
            
            // 递归添加子分类的后代
            $descendants = $descendants->merge($child->descendants());
        }
        
        return $descendants;
    }
}
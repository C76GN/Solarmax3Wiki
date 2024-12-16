<?php
// FileName: /var/www/Solarmax3Wiki/app/Models/WikiCategory.php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\LogsActivity;

class WikiCategory extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'parent_id',
        'order'
    ];

    // 与父分类的关系
    public function parent()
    {
        return $this->belongsTo(WikiCategory::class, 'parent_id');
    }

    // 与子分类的关系
    public function children()
    {
        return $this->hasMany(WikiCategory::class, 'parent_id');
    }

    // 与页面的关系
    public function pages()
    {
        return $this->belongsToMany(WikiPage::class, 'wiki_page_category');
    }

    // 获取分类的完整路径
    public function getPathAttribute()
    {
        $path = collect([$this]);
        $parent = $this->parent;

        while($parent) {
            $path->prepend($parent);
            $parent = $parent->parent;
        }

        return $path;
    }

    // 获取所有后代分类
    public function descendants()
    {
        $descendants = collect();
        
        foreach($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->descendants());
        }
        
        return $descendants;
    }
}
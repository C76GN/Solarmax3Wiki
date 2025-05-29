<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * WikiTag 模型
 * 表示 Wiki 页面标签。
 */
class WikiTag extends Model
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
    ];

    /**
     * 获取此标签关联的 Wiki 页面。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function pages(): BelongsToMany
    {
        return $this->belongsToMany(WikiPage::class, 'wiki_page_tag');
    }
}
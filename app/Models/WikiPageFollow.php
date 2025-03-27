<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WikiPageFollow 模型 - 用于管理用户与Wiki页面之间的关注关系
 * 
 * @property int $id 主键ID
 * @property int $user_id 关注者用户ID
 * @property int $wiki_page_id 被关注的Wiki页面ID
 * @property \Carbon\Carbon $created_at 创建时间
 * @property \Carbon\Carbon $updated_at 更新时间
 * 
 * @property-read \App\Models\User $user 关联的用户
 * @property-read \App\Models\WikiPage $page 关联的Wiki页面
 */
class WikiPageFollow extends Model
{
    /**
     * 可批量赋值的属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'wiki_page_id'
    ];

    /**
     * 获取关注记录关联的用户
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 获取关注记录关联的Wiki页面
     *
     * @return BelongsTo
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(WikiPage::class, 'wiki_page_id');
    }
}
<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * WikiComment 模型
 * 表示 Wiki 页面上的评论及其回复。
 */
class WikiComment extends Model
{
    use LogsActivity;

    /**
     * 可批量赋值的属性。
     *
     * @var array
     */
    protected $fillable = [
        'wiki_page_id',    // 评论所属的 Wiki 页面 ID
        'user_id',         // 评论发布者的用户 ID
        'parent_id',       // 如果是回复，则为父评论的 ID
        'content',         // 评论内容
        'is_hidden',       // 评论是否被隐藏（例如：被删除或审核）
    ];

    /**
     * 模型属性的类型转换。
     *
     * @var array
     */
    protected $casts = [
        'is_hidden' => 'boolean', // 将 is_hidden 转换为布尔值
    ];

    /**
     * 获取评论所属的 Wiki 页面。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(WikiPage::class, 'wiki_page_id');
    }

    /**
     * 获取发布此评论的用户。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 获取此评论的父评论（如果存在）。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(WikiComment::class, 'parent_id');
    }

    /**
     * 获取此评论的所有回复（子评论）。
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function replies(): HasMany
    {
        return $this->hasMany(WikiComment::class, 'parent_id');
    }
}
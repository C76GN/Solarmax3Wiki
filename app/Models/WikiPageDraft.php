<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WikiPageDraft 模型
 * 表示用户对 Wiki 页面的编辑草稿。
 */
class WikiPageDraft extends Model
{
    /**
     * 可批量赋值的属性。
     *
     * @var array
     */
    protected $fillable = [
        'wiki_page_id',
        'user_id',
        'content',
        'last_saved_at',
    ];

    /**
     * 属性类型转换。
     *
     * @var array
     */
    protected $casts = [
        'last_saved_at' => 'datetime',
    ];

    /**
     * 获取此草稿所属的 Wiki 页面。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(WikiPage::class, 'wiki_page_id');
    }

    /**
     * 获取创建此草稿的用户。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WikiPageIssue 模型 - 管理 Wiki 页面的问题报告
 *
 * @property int $id 主键ID
 * @property int $wiki_page_id 关联的Wiki页面ID
 * @property int $reported_by 报告者的用户ID
 * @property string $content 问题内容
 * @property string $status 问题状态(to_be_solved/handled)
 * @property int|null $created_by 创建者ID
 * @property \Carbon\Carbon $created_at 创建时间
 * @property \Carbon\Carbon $updated_at 更新时间
 * 
 * @property-read \App\Models\User $reporter 问题报告者
 * @property-read \App\Models\WikiPage $wikiPage 关联的Wiki页面
 */
class WikiPageIssue extends Model
{
    use HasFactory, LogsActivity;

    /**
     * 问题状态常量
     */
    const STATUS_TO_BE_SOLVED = 'to_be_solved';
    const STATUS_HANDLED = 'handled';

    /**
     * 可批量赋值的属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'wiki_page_id',
        'reported_by',
        'content',
        'status',
        'created_by',
    ];

    /**
     * 需要类型转换的属性
     *
     * @var array<string, string>
     */
    protected $casts = [
        'updated_at' => 'datetime',
        'view_count' => 'integer'
    ];

    /**
     * 获取报告此问题的用户
     *
     * @return BelongsTo
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    /**
     * 获取关联的Wiki页面
     *
     * @return BelongsTo
     */
    public function wikiPage(): BelongsTo
    {
        return $this->belongsTo(WikiPage::class, 'wiki_page_id');
    }
}
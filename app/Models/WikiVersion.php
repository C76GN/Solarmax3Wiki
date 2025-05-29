<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WikiVersion 模型
 * 表示 Wiki 页面的一个历史版本。
 */
class WikiVersion extends Model
{
    use LogsActivity;

    /**
     * 可批量赋值的属性。
     *
     * @var array
     */
    protected $fillable = [
        'wiki_page_id',       // 关联的 Wiki 页面 ID
        'content',            // 版本内容
        'created_by',         // 创建此版本的用户 ID
        'version_number',     // 版本号
        'comment',            // 版本更新说明
        'is_current',         // 是否为当前页面最新版本
        'diff_from_previous', // 与前一版本的差异数据
    ];

    /**
     * 模型属性的类型转换。
     *
     * @var array
     */
    protected $casts = [
        'is_current' => 'boolean', // 将 is_current 转换为布尔值
        'diff_from_previous' => 'array', // 将 diff_from_previous 转换为数组
    ];

    /**
     * 获取此版本所属的 Wiki 页面。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(WikiPage::class, 'wiki_page_id');
    }

    /**
     * 获取创建此版本的用户。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * 计算当前版本内容与前一版本内容的差异。
     *
     * @param string $previousContent 前一版本的内容。
     * @return array 差异的数组表示。
     */
    public function calculateDiff(string $previousContent): array
    {
        return []; // 差异计算逻辑待实现
    }
}
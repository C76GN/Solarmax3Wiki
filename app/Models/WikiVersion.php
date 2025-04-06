<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\LogsActivity;

class WikiVersion extends Model
{
    use LogsActivity;
    
    protected $fillable = [
        'wiki_page_id',
        'content',
        'created_by',
        'version_number',
        'comment',
        'is_current',
        'diff_from_previous'
    ];
    
    protected $casts = [
        'is_current' => 'boolean',
        'diff_from_previous' => 'array'
    ];
    
    // 关联页面
    public function page(): BelongsTo
    {
        return $this->belongsTo(WikiPage::class, 'wiki_page_id');
    }
    
    // 关联创建者
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    // 计算与前一版本的差异
    public function calculateDiff(string $previousContent): array
    {
        // 这里会实现差异计算逻辑
        // 可以使用外部库如diff-match-patch
        // 返回值示例: ['added' => [...], 'removed' => [...], 'changed' => [...]]
        return [];
    }
}
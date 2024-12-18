<?php
// FileName: /var/www/Solarmax3Wiki/app/Models/WikiPageRevision.php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WikiPageRevision extends Model
{
    protected $fillable = [
        'wiki_page_id',
        'title',
        'content',
        'comment',
        'created_by',
        'changes',
        'version'
    ];

    protected $casts = [
        'changes' => 'json'
    ];

    // 关联到页面
    public function page()
    {
        return $this->belongsTo(WikiPage::class, 'wiki_page_id');
    }

    // 关联到创建者
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // 计算与前一个版本的差异
    public function calculateChanges($previousContent)
    {
        if (!$previousContent) {
            return ['type' => 'create', 'content' => $this->content];
        }

        // 基础的差异计算
        $changes = [];
        $oldLines = explode("\n", $previousContent);
        $newLines = explode("\n", $this->content);
        
        $changes['added'] = array_values(array_diff($newLines, $oldLines));
        $changes['removed'] = array_values(array_diff($oldLines, $newLines));
        
        return $changes;
    }
}
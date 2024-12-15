<?php

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

    public function page()
    {
        return $this->belongsTo(WikiPage::class, 'wiki_page_id');
    }

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

        // 这里使用简单的差异计算，可以根据需要使用更复杂的diff算法
        $changes = [];
        $oldLines = explode("\n", $previousContent);
        $newLines = explode("\n", $this->content);
        
        $changes['added'] = array_diff($newLines, $oldLines);
        $changes['removed'] = array_diff($oldLines, $newLines);
        
        return $changes;
    }
}
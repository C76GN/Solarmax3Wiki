<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WikiPageReference extends Model
{
    protected $fillable = [
        'source_page_id',
        'target_page_id',
        'context'
    ];

    public function sourcePage()
    {
        return $this->belongsTo(WikiPage::class, 'source_page_id');
    }

    public function targetPage()
    {
        return $this->belongsTo(WikiPage::class, 'target_page_id');
    }
}
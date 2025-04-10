<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class WikiTag extends Model
{
    use LogsActivity;

    protected $fillable = [
        'name',
        'slug',
    ];

    // 关联页面
    public function pages(): BelongsToMany
    {
        return $this->belongsToMany(WikiPage::class, 'wiki_page_tag');
    }
}

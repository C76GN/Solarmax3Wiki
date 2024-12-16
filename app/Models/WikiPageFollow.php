<?php
// FileName: /var/www/Solarmax3Wiki/app/Models/WikiPageFollow.php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WikiPageFollow extends Model
{
    protected $fillable = [
        'user_id',
        'wiki_page_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function page()
    {
        return $this->belongsTo(WikiPage::class, 'wiki_page_id');
    }
}
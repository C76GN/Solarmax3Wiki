<?php
// FileName: /var/www/Solarmax3Wiki/app/Models/WikiPage.php


namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WikiPageIssue extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'wiki_page_id',
        'reported_by',
        'content',
        'status',
        'created_by',
    ];

    protected $casts = [
        'updated_at' => 'datetime',
        'view_count' => 'integer'
    ];

}

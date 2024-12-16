<?php
// FileName: /var/www/Solarmax3Wiki/app/Models/Permission.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'group'
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }
}
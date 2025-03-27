<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * 权限模型
 * 
 * 表示系统中的权限项，用于基于角色的权限控制
 */
class Permission extends Model
{
    /**
     * 可批量赋值的属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',        // 权限标识符，例如 'users.create'
        'display_name', // 权限显示名称，例如 '创建用户'
        'description',  // 权限描述
        'group'         // 权限分组
    ];

    /**
     * 获取拥有此权限的角色
     *
     * @return BelongsToMany 与角色的多对多关联
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }
}
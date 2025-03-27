<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * 角色模型
 * 
 * 表示系统中的用户角色，用于基于角色的权限控制系统
 * 每个角色可以拥有多个权限，多个用户可以拥有同一个角色
 */
class Role extends Model
{
    /**
     * 可批量赋值的属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',         // 角色标识符，例如 'admin'
        'display_name', // 角色显示名称，例如 '管理员'
        'description',  // 角色描述
        'is_system'     // 是否为系统角色（系统角色通常不可编辑或删除）
    ];

    /**
     * 属性的类型转换
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_system' => 'boolean'
    ];

    /**
     * 获取拥有此角色的用户
     *
     * @return BelongsToMany 与用户的多对多关联
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * 获取此角色拥有的权限
     *
     * @return BelongsToMany 与权限的多对多关联
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    /**
     * 检查角色是否拥有指定权限
     *
     * @param string|Permission $permission 权限名称或权限对象
     * @return bool 是否拥有权限
     */
    public function hasPermission($permission): bool
    {
        // 如果参数是字符串，按权限名称查找
        if (is_string($permission)) {
            return $this->permissions->contains('name', $permission);
        }
        
        // 否则按权限对象查找
        return $this->permissions->contains($permission);
    }
}
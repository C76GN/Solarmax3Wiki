<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use App\Traits\LogsActivity;

/**
 * 用户模型
 * 
 * 表示系统中的用户账户，支持角色和权限管理
 * 实现了邮箱验证接口
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, LogsActivity;

    /**
     * 可批量赋值的属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',      // 用户姓名
        'email',     // 电子邮件地址
        'password',  // 密码（已哈希）
    ];

    /**
     * 隐藏属性（不会在数组或JSON中出现）
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',       // 密码（安全原因）
        'remember_token', // 记住登录令牌（安全原因）
    ];

    /**
     * 属性的类型转换
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime', // 邮箱验证时间
            'password' => 'hashed',            // 密码（自动哈希处理）
        ];
    }

    /**
     * 获取用户拥有的角色
     *
     * @return BelongsToMany 与角色的多对多关联
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * 检查用户是否拥有指定角色
     *
     * @param string|Role $role 角色名称或角色对象
     * @return bool 是否拥有角色
     */
    public function hasRole($role): bool
    {
        // 如果参数是字符串，按角色名称查找
        if (is_string($role)) {
            return $this->roles->contains('name', $role);
        }
        
        // 否则按角色对象查找
        return $this->roles->contains($role);
    }

    /**
     * 检查用户是否拥有指定权限
     * 
     * 如果用户拥有ID为1的角色（超级管理员），则自动拥有所有权限
     *
     * @param string|Permission $permission 权限名称或权限对象
     * @return bool 是否拥有权限
     */
    public function hasPermission($permission): bool
    {
        // 检查用户是否拥有超级管理员角色（ID为1）
        $isAdmin = $this->roles->contains('id', 1);
        if ($isAdmin) {
            return true;
        }
        
        // 获取权限名称
        $permissionName = is_string($permission) ? $permission : $permission->name;
        
        // 从所有角色中收集权限，检查是否包含所需权限
        return $this->roles->flatMap(function ($role) {
            return $role->permissions;
        })->contains('name', $permissionName);
    }

    /**
     * 检查用户是否拥有指定角色列表中的任意一个
     *
     * @param array $roles 角色名称数组
     * @return bool 是否拥有任意一个角色
     */
    public function hasAnyRole(array $roles): bool
    {
        return $this->roles->whereIn('name', $roles)->isNotEmpty();
    }

    /**
     * 检查用户是否拥有指定权限列表中的任意一个
     *
     * @param array $permissions 权限名称数组
     * @return bool 是否拥有任意一个权限
     */
    public function hasAnyPermission(array $permissions): bool
    {
        return $this->roles->flatMap(function ($role) {
            return $role->permissions;
        })->whereIn('name', $permissions)->isNotEmpty();
    }

    /**
     * 获取用户所有权限的集合
     * 
     * 通过角色获取所有权限，去重后返回权限名称数组
     *
     * @return array 权限名称数组
     */
    public function getAllPermissionsAttribute(): array
    {
        return $this->roles->flatMap(function ($role) {
            return $role->permissions;
        })->pluck('name')->unique()->values()->all();
    }
}
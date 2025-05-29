<?php

namespace App\Models;

// 导入活动日志特性
use App\Traits\LogsActivity;
// 导入强制验证邮箱接口
use Illuminate\Contracts\Auth\MustVerifyEmail;
// 导入模型工厂特性
use Illuminate\Database\Eloquent\Factories\HasFactory;
// 导入认证用户基类
use Illuminate\Foundation\Auth\User as Authenticatable;
// 导入通知特性
use Illuminate\Notifications\Notifiable;
// 导入 Spatie 权限管理特性
use Spatie\Permission\Traits\HasRoles;

/**
 * 用户模型
 *
 * 表示系统中的用户，管理其认证、角色和权限。
 */
class User extends Authenticatable implements MustVerifyEmail
{
    // 使用模型工厂、角色权限管理、活动日志和通知特性
    use HasFactory, HasRoles, LogsActivity, Notifiable;

    /**
     * 可批量赋值的属性。
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * 应该在数组中隐藏的属性。
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * 属性类型转换。
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
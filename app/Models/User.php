<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;//用户邮件验证接口
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
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

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function hasRole($role)
    {
        if (is_string($role)) {
            return $this->roles->contains('name', $role);
        }
        return $this->roles->contains($role);
    }

    public function hasPermission($permission)
    {
        return $this->roles->flatMap(function ($role) {
            return $role->permissions;
        })->contains('name', is_string($permission) ? $permission : $permission->name);
    }

    public function hasAnyRole(array $roles)
    {
        return $this->roles->whereIn('name', $roles)->isNotEmpty();
    }

    public function hasAnyPermission(array $permissions)
    {
        return $this->roles->flatMap(function ($role) {
            return $role->permissions;
        })->whereIn('name', $permissions)->isNotEmpty();
    }

    public function getAllPermissionsAttribute()
    {
        return $this->roles->flatMap(function ($role) {
            return $role->permissions;
        })->pluck('name')->unique()->values()->all();
    }
}

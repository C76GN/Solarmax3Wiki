<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        // 创建权限
        $permissions = [
            // Wiki文章权限
            ['name' => 'wiki.view', 'display_name' => '查看Wiki文章', 'group' => 'wiki'],
            ['name' => 'wiki.create', 'display_name' => '创建Wiki文章', 'group' => 'wiki'],
            ['name' => 'wiki.edit', 'display_name' => '编辑Wiki文章', 'group' => 'wiki'],
            ['name' => 'wiki.delete', 'display_name' => '删除Wiki文章', 'group' => 'wiki'],
            ['name' => 'wiki.publish', 'display_name' => '发布Wiki文章', 'group' => 'wiki'],

            // 角色权限
            ['name' => 'role.view', 'display_name' => '查看角色', 'group' => 'role'],
            ['name' => 'role.create', 'display_name' => '创建角色', 'group' => 'role'],
            ['name' => 'role.edit', 'display_name' => '编辑角色', 'group' => 'role'],
            ['name' => 'role.delete', 'display_name' => '删除角色', 'group' => 'role'],

            // 用户权限
            ['name' => 'user.view', 'display_name' => '查看用户', 'group' => 'user'],
            ['name' => 'user.edit', 'display_name' => '编辑用户角色', 'group' => 'user'],

            // 日志权限
            ['name' => 'log.view', 'display_name' => '查看系统日志', 'group' => 'log'],
        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }

        // 创建角色
        $adminRole = Role::create([
            'name' => 'admin',
            'display_name' => '管理员',
            'description' => '系统管理员',
            'is_system' => true,
        ]);

        $editorRole = Role::create([
            'name' => 'editor',
            'display_name' => '编辑',
            'description' => '内容编辑',
            'is_system' => false,
        ]);

        // 分配权限给角色
        $adminRole->permissions()->attach(Permission::all());
        $editorRole->permissions()->attach(
            Permission::whereIn('name', [
                'wiki.view',
                'wiki.create',
                'wiki.edit',
                'wiki.publish'
            ])->get()
        );

        // 为测试用户分配管理员角色
        $user = User::where('email', 'test@example.com')->first();
        if ($user) {
            $user->roles()->attach($adminRole);
        }
    }
}
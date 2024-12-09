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
            // 模板权限
            ['name' => 'template.view', 'display_name' => '查看模板', 'group' => 'template'],
            ['name' => 'template.create', 'display_name' => '创建模板', 'group' => 'template'],
            ['name' => 'template.edit', 'display_name' => '编辑模板', 'group' => 'template'],
            ['name' => 'template.delete', 'display_name' => '删除模板', 'group' => 'template'],

            // 页面权限
            ['name' => 'page.view', 'display_name' => '查看页面', 'group' => 'page'],
            ['name' => 'page.create', 'display_name' => '创建页面', 'group' => 'page'],
            ['name' => 'page.edit', 'display_name' => '编辑页面', 'group' => 'page'],
            ['name' => 'page.delete', 'display_name' => '删除页面', 'group' => 'page'],
            ['name' => 'page.publish', 'display_name' => '发布页面', 'group' => 'page'],
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
                'template.view',
                'page.view',
                'page.create',
                'page.edit',
                'page.publish'
            ])->get()
        );

        // 为测试用户分配管理员角色
        $user = User::where('email', 'test@example.com')->first();
        if ($user) {
            $user->roles()->attach($adminRole);
        }
    }
}
<?php
// FileName: /var/www/Solarmax3Wiki/database/seeders/PermissionSeeder.php


namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        // 定义所有权限
        $permissions = [
            // Wiki页面权限
            ['name' => 'wiki.view', 'display_name' => '查看Wiki页面', 'group' => 'wiki'],
            ['name' => 'wiki.create', 'display_name' => '创建Wiki页面', 'group' => 'wiki'],
            ['name' => 'wiki.edit', 'display_name' => '编辑Wiki页面', 'group' => 'wiki'],
            ['name' => 'wiki.delete', 'display_name' => '删除Wiki页面', 'group' => 'wiki'],
            ['name' => 'wiki.publish', 'display_name' => '发布Wiki页面', 'group' => 'wiki'],

            // Wiki分类权限
            ['name' => 'wiki.category.view', 'display_name' => '查看Wiki分类', 'group' => 'wiki'],
            ['name' => 'wiki.category.create', 'display_name' => '创建Wiki分类', 'group' => 'wiki'],
            ['name' => 'wiki.category.edit', 'display_name' => '编辑Wiki分类', 'group' => 'wiki'],
            ['name' => 'wiki.category.delete', 'display_name' => '删除Wiki分类', 'group' => 'wiki'],

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

            [
                'name' => 'wiki.manage_trash',
                'display_name' => '管理回收站',
                'group' => 'wiki'
            ],
        ];

        // 创建或更新权限
        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name']], // 查找条件
                $permission // 更新/创建的数据
            );
        }

        // 创建或获取管理员角色
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            [
                'display_name' => '管理员',
                'description' => '系统管理员',
                'is_system' => true,
            ]
        );

        // 创建或获取编辑角色
        $editorRole = Role::firstOrCreate(
            ['name' => 'editor'],
            [
                'display_name' => '编辑',
                'description' => '内容编辑',
                'is_system' => false,
            ]
        );

        // 分配权限给角色
        $adminRole->permissions()->sync(Permission::all());
        $editorRole->permissions()->sync(
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
            $user->roles()->sync([$adminRole->id]);
        }
    }
}
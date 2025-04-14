<?php

namespace Database\Seeders;

use App\Models\User; // 保持 User 模型引用
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Seeder;
// 使用 Spatie 的模型
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    private const ROLE_ADMIN = 'admin';

    private const ROLE_EDITOR = 'editor';

    private const ROLE_RESOLVER = 'conflict_resolver';

    // 这些常量可以保留，用于逻辑分组，但不再存入数据库
    private const GROUP_ROLE = 'role';

    private const GROUP_USER = 'user';

    private const GROUP_LOG = 'log';

    private const GROUP_WIKI = 'wiki';

    private const GROUP_WIKI_TRASH = 'wiki_trash';

    public function run(): void
    {
        $this->command->info('开始使用 Spatie 模型初始化系统权限与角色...');

        // 清空 Spatie 的权限缓存
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 创建或获取所有需要的权限
        $permissions = $this->createPermissions();
        if ($permissions->isEmpty() && count($this->getPermissionsConfig()) > 0) {
            $this->command->error('权限未能成功创建或获取，请检查数据库和 Spatie 配置。');

            return;
        }
        $this->command->info("共找到/创建了 {$permissions->count()} 个权限点。");

        // 创建角色并同步权限
        $adminRole = $this->createAdminRole($permissions);
        $editorRole = $this->createEditorRole($permissions);
        $resolverRole = $this->createConflictResolverRole($permissions);

        // 给测试用户分配角色
        $this->assignRolesToTestUsers($adminRole, $editorRole, $resolverRole);

        $this->command->info('系统权限与角色初始化完成！');
    }

    /**
     * 创建或查找所有权限，只使用 name 和 guard_name
     */
    private function createPermissions(): EloquentCollection
    {
        $permissionsConfig = $this->getPermissionsConfig();
        $permissionNames = collect($permissionsConfig)->pluck('name');
        $count = 0;

        foreach ($permissionNames as $permissionName) {
            try {
                // 只使用 name 和 guard_name 创建，其他信息忽略
                Permission::firstOrCreate([
                    'name' => $permissionName,
                    'guard_name' => 'web', // 默认 guard
                ]);
                $count++;
            } catch (\Exception $e) {
                $this->command->error("创建/查找权限 '{$permissionName}' 时出错: ".$e->getMessage());
            }
        }

        // 返回所有在配置中定义的权限的模型实例
        return Permission::whereIn('name', $permissionNames)->where('guard_name', 'web')->get();
    }

    /**
     * 创建或查找管理员角色，只使用 name 和 guard_name
     */
    private function createAdminRole(EloquentCollection $permissions): Role
    {
        // 只使用 name 和 guard_name 创建，忽略 display_name, description
        $adminRole = Role::firstOrCreate(
            ['name' => self::ROLE_ADMIN, 'guard_name' => 'web']
            // 第二个参数（默认值）为空，因为 Spatie 表没有这些列
        );

        try {
            // 使用权限模型实例集合进行同步
            $adminRole->syncPermissions($permissions);
            $this->command->info("管理员角色创建/更新完成，已同步所有 {$permissions->count()} 个权限。");
        } catch (\Exception $e) {
            $this->command->error('为管理员角色同步权限时出错: '.$e->getMessage());
        }

        return $adminRole;
    }

    /**
     * 创建或查找编辑角色，只使用 name 和 guard_name
     */
    private function createEditorRole(EloquentCollection $permissions): Role
    {
        // 只使用 name 和 guard_name 创建
        $editorRole = Role::firstOrCreate(
            ['name' => self::ROLE_EDITOR, 'guard_name' => 'web']
        );

        $editorPermissionNames = [
            'wiki.view',
            'wiki.create',
            'wiki.edit',
            'wiki.comment',
            'wiki.history',
            'wiki.delete',
        ];
        try {
            // 直接使用权限名称数组进行同步
            $editorRole->syncPermissions($editorPermissionNames);
            $this->command->info('编辑角色创建/更新完成，已分配 '.count($editorPermissionNames).' 个相关权限。');
        } catch (\Exception $e) {
            $this->command->error('为编辑角色同步权限时出错: '.$e->getMessage());
        }

        return $editorRole;
    }

    /**
     * 创建或查找冲突解决者角色，只使用 name 和 guard_name
     */
    private function createConflictResolverRole(EloquentCollection $permissions): Role
    {
        // 只使用 name 和 guard_name 创建
        $resolverRole = Role::firstOrCreate(
            ['name' => self::ROLE_RESOLVER, 'guard_name' => 'web']
        );

        $resolverPermissionNames = [
            'wiki.view',
            'wiki.resolve_conflict',
            'wiki.history',
        ];
        try {
            $resolverRole->syncPermissions($resolverPermissionNames);
            $this->command->info('冲突解决者角色创建/更新完成，已分配 '.count($resolverPermissionNames).' 个相关权限。');
        } catch (\Exception $e) {
            $this->command->error('为冲突解决者角色同步权限时出错: '.$e->getMessage());
        }

        return $resolverRole;
    }

    /**
     * 为测试用户分配角色 (保持不变)
     */
    private function assignRolesToTestUsers(Role $adminRole, Role $editorRole, Role $resolverRole): void
    {
        $this->assignRoleToUser('admin@example.com', $adminRole, '管理员');
        $this->assignRoleToUser('editor@example.com', $editorRole, '编辑');
        $this->assignRoleToUser('resolver@example.com', $resolverRole, '冲突解决者');

        $user = User::where('email', 'user@example.com')->first();
        if ($user) {
            $user->removeRole($adminRole);
            $user->removeRole($editorRole);
            $user->removeRole($resolverRole);
            $this->command->info('已确保用户 user@example.com 没有管理员、编辑或冲突解决者角色。');
        } else {
            $this->command->warn('未找到测试用户 user@example.com');
        }
    }

    /**
     * 辅助函数：为单个用户分配角色 (保持不变)
     */
    private function assignRoleToUser(string $email, Role $role, string $roleDisplayName): void
    {
        $user = User::where('email', $email)->first();
        if ($user) {
            try {
                $user->assignRole($role); // Spatie 方法
                $this->command->info("已为用户 {$email} 分配 '{$roleDisplayName}' 角色。({$role->name})"); // 可以加上内部 name
            } catch (\Exception $e) {
                $this->command->error("为用户 {$email} 分配角色 '{$roleDisplayName}' 时出错: ".$e->getMessage());
            }
        } else {
            $this->command->warn("未找到测试用户 {$email}，跳过角色分配。");
        }
    }

    /**
     * 获取权限配置数组 (保持不变, 但注意这里的 display_name 等不再存入数据库)
     */
    private function getPermissionsConfig(): array
    {
        // 这个数组定义了你应用需要的权限逻辑名称，但 display_name 等不会存入 Spatie 的表
        return [
            ['name' => 'wiki.view', 'display_name' => '查看Wiki页面', 'group' => self::GROUP_WIKI, 'description' => '允许查看所有公开的Wiki页面'],
            ['name' => 'wiki.create', 'display_name' => '创建Wiki页面', 'group' => self::GROUP_WIKI, 'description' => '允许创建新的Wiki页面'],
            ['name' => 'wiki.edit', 'display_name' => '编辑Wiki页面', 'group' => self::GROUP_WIKI, 'description' => '允许编辑自己或他人创建的Wiki页面'],
            ['name' => 'wiki.delete', 'display_name' => '删除(移至回收站)Wiki页面', 'group' => self::GROUP_WIKI, 'description' => '允许将Wiki页面移至回收站'],
            ['name' => 'wiki.history', 'display_name' => '查看Wiki历史', 'group' => self::GROUP_WIKI, 'description' => '允许查看页面的编辑历史和版本差异'],
            ['name' => 'wiki.revert', 'display_name' => '恢复Wiki版本', 'group' => self::GROUP_WIKI, 'description' => '允许将页面恢复到指定的历史版本'],
            ['name' => 'wiki.comment', 'display_name' => '评论Wiki页面', 'group' => self::GROUP_WIKI, 'description' => '允许在Wiki页面下发表评论和回复'],
            ['name' => 'wiki.resolve_conflict', 'display_name' => '解决Wiki冲突', 'group' => self::GROUP_WIKI, 'description' => '允许访问冲突解决界面并合并/选择冲突版本'],
            ['name' => 'wiki.manage_categories', 'display_name' => '管理Wiki分类', 'group' => self::GROUP_WIKI, 'description' => '允许创建、编辑、删除Wiki分类'],
            ['name' => 'wiki.manage_tags', 'display_name' => '管理Wiki标签', 'group' => self::GROUP_WIKI, 'description' => '允许创建、编辑、删除Wiki标签'],
            ['name' => 'wiki.moderate_comments', 'display_name' => '管理Wiki评论', 'group' => self::GROUP_WIKI, 'description' => '允许编辑或删除他人的评论'],
            ['name' => 'wiki.trash.view', 'display_name' => '查看回收站', 'group' => self::GROUP_WIKI_TRASH, 'description' => '允许查看回收站中的页面'],
            ['name' => 'wiki.trash.restore', 'display_name' => '恢复回收站页面', 'group' => self::GROUP_WIKI_TRASH, 'description' => '允许从回收站恢复页面'],
            ['name' => 'wiki.trash.force_delete', 'display_name' => '永久删除回收站页面', 'group' => self::GROUP_WIKI_TRASH, 'description' => '允许永久删除回收站中的页面'],
            ['name' => 'role.view', 'display_name' => '查看角色', 'group' => self::GROUP_ROLE, 'description' => '允许查看系统中的所有角色及其权限'],
            ['name' => 'role.create', 'display_name' => '创建角色', 'group' => self::GROUP_ROLE, 'description' => '允许创建新的用户角色'],
            ['name' => 'role.edit', 'display_name' => '编辑角色', 'group' => self::GROUP_ROLE, 'description' => '允许编辑现有角色的名称、描述和权限'], // 注意：编辑时只能改 Spatie 表中的字段
            ['name' => 'role.delete', 'display_name' => '删除角色', 'group' => self::GROUP_ROLE, 'description' => '允许删除用户角色（系统角色除外）'],
            ['name' => 'user.view', 'display_name' => '查看用户', 'group' => self::GROUP_USER, 'description' => '允许查看系统中的所有用户列表'],
            ['name' => 'user.edit', 'display_name' => '编辑用户角色', 'group' => self::GROUP_USER, 'description' => '允许修改用户的角色分配'],
            ['name' => 'log.view', 'display_name' => '查看系统日志', 'group' => self::GROUP_LOG, 'description' => '允许查看系统的活动日志和操作记录'],
        ];
    }
}

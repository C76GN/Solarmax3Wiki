<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * 权限播种器。
 * 使用 Spatie/laravel-permission 包初始化系统中的权限和角色。
 */
class PermissionSeeder extends Seeder
{
    // 定义常用角色的系统名称
    private const ROLE_ADMIN = 'admin';
    private const ROLE_EDITOR = 'editor';
    private const ROLE_RESOLVER = 'conflict_resolver';

    // 定义权限分组的逻辑名称（仅用于配置和显示）
    private const GROUP_ROLE = 'role';
    private const GROUP_USER = 'user';
    private const GROUP_LOG = 'log';
    private const GROUP_WIKI = 'wiki';
    private const GROUP_WIKI_TRASH = 'wiki_trash';

    /**
     * 运行数据库播种。
     *
     * 该方法负责清空权限缓存、创建所有定义的权限、创建并配置核心角色，
     * 并为测试用户分配这些角色。
     */
    public function run(): void
    {
        $this->command->info('开始使用 Spatie 模型初始化系统权限与角色...');

        // 清空 Spatie 的权限缓存，确保加载最新的权限配置
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 创建或获取所有在配置中定义的权限
        $permissions = $this->createPermissions();
        if ($permissions->isEmpty() && count($this->getPermissionsConfig()) > 0) {
            $this->command->error('权限未能成功创建或获取，请检查数据库和 Spatie 配置。');

            return;
        }
        $this->command->info("共找到/创建了 {$permissions->count()} 个权限点。");

        // 创建并配置核心角色
        $adminRole = $this->createAdminRole($permissions);
        $editorRole = $this->createEditorRole($permissions);
        $resolverRole = $this->createConflictResolverRole($permissions);

        // 为预设的测试用户分配角色
        $this->assignRolesToTestUsers($adminRole, $editorRole, $resolverRole);

        $this->command->info('系统权限与角色初始化完成！');
    }

    /**
     * 根据 `getPermissionsConfig` 中定义的列表创建或查找所有权限。
     *
     * @return EloquentCollection 包含所有创建/查找到的权限模型的集合。
     */
    private function createPermissions(): EloquentCollection
    {
        $permissionsConfig = $this->getPermissionsConfig();
        $permissionNames = collect($permissionsConfig)->pluck('name');
        $count = 0;

        foreach ($permissionNames as $permissionName) {
            try {
                // 如果权限不存在则创建，否则返回现有实例
                Permission::firstOrCreate([
                    'name' => $permissionName,
                    'guard_name' => 'web', // 默认的守卫名称
                ]);
                $count++;
            } catch (\Exception $e) {
                $this->command->error("创建/查找权限 '{$permissionName}' 时出错: ".$e->getMessage());
            }
        }

        // 返回所有匹配名称和守卫的权限
        return Permission::whereIn('name', $permissionNames)->where('guard_name', 'web')->get();
    }

    /**
     * 创建或查找管理员角色，并为其分配所有可用权限。
     *
     * @param  EloquentCollection  $permissions  所有可用权限的集合。
     * @return Role 管理员角色实例。
     */
    private function createAdminRole(EloquentCollection $permissions): Role
    {
        // 创建或查找管理员角色
        $adminRole = Role::firstOrCreate(
            ['name' => self::ROLE_ADMIN, 'guard_name' => 'web']
        );

        try {
            // 将所有权限同步给管理员角色
            $adminRole->syncPermissions($permissions);
            $this->command->info("管理员角色创建/更新完成，已同步所有 {$permissions->count()} 个权限。");
        } catch (\Exception $e) {
            $this->command->error('为管理员角色同步权限时出错: '.$e->getMessage());
        }

        return $adminRole;
    }

    /**
     * 创建或查找编辑角色，并为其分配特定权限（如Wiki页面的创建、编辑等）。
     *
     * @param  EloquentCollection  $permissions  所有可用权限的集合 (此方法中未直接使用此参数)。
     * @return Role 编辑角色实例。
     */
    private function createEditorRole(EloquentCollection $permissions): Role
    {
        // 创建或查找编辑角色
        $editorRole = Role::firstOrCreate(
            ['name' => self::ROLE_EDITOR, 'guard_name' => 'web']
        );

        // 定义编辑角色所需的权限名称列表
        $editorPermissionNames = [
            'wiki.view',
            'wiki.create',
            'wiki.edit',
            'wiki.comment',
            'wiki.history',
            'wiki.delete',
        ];
        try {
            // 将指定权限同步给编辑角色
            $editorRole->syncPermissions($editorPermissionNames);
            $this->command->info('编辑角色创建/更新完成，已分配 '.count($editorPermissionNames).' 个相关权限。');
        } catch (\Exception $e) {
            $this->command->error('为编辑角色同步权限时出错: '.$e->getMessage());
        }

        return $editorRole;
    }

    /**
     * 创建或查找冲突解决者角色，并为其分配解决Wiki页面冲突的权限。
     *
     * @param  EloquentCollection  $permissions  所有可用权限的集合 (此方法中未直接使用此参数)。
     * @return Role 冲突解决者角色实例。
     */
    private function createConflictResolverRole(EloquentCollection $permissions): Role
    {
        // 创建或查找冲突解决者角色
        $resolverRole = Role::firstOrCreate(
            ['name' => self::ROLE_RESOLVER, 'guard_name' => 'web']
        );

        // 定义冲突解决者角色所需的权限名称列表
        $resolverPermissionNames = [
            'wiki.view',
            'wiki.resolve_conflict',
            'wiki.history',
        ];
        try {
            // 将指定权限同步给冲突解决者角色
            $resolverRole->syncPermissions($resolverPermissionNames);
            $this->command->info('冲突解决者角色创建/更新完成，已分配 '.count($resolverPermissionNames).' 个相关权限。');
        } catch (\Exception $e) {
            $this->command->error('为冲突解决者角色同步权限时出错: '.$e->getMessage());
        }

        return $resolverRole;
    }

    /**
     * 为预定义的测试用户分配相应的角色。
     *
     * @param  Role  $adminRole  管理员角色实例。
     * @param  Role  $editorRole  编辑角色实例。
     * @param  Role  $resolverRole  冲突解决者角色实例。
     */
    private function assignRolesToTestUsers(Role $adminRole, Role $editorRole, Role $resolverRole): void
    {
        $this->assignRoleToUser('admin@example.com', $adminRole, '管理员');
        $this->assignRoleToUser('editor@example.com', $editorRole, '编辑');
        $this->assignRoleToUser('resolver@example.com', $resolverRole, '冲突解决者');

        // 确保普通测试用户没有特殊角色
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
     * 辅助函数：为指定邮箱的用户分配角色。
     *
     * @param  string  $email  用户的邮箱地址。
     * @param  Role  $role  要分配的角色实例。
     * @param  string  $roleDisplayName  角色的显示名称（用于控制台输出）。
     */
    private function assignRoleToUser(string $email, Role $role, string $roleDisplayName): void
    {
        $user = User::where('email', $email)->first();
        if ($user) {
            try {
                $user->assignRole($role); // 分配角色
                $this->command->info("已为用户 {$email} 分配 '{$roleDisplayName}' 角色。({$role->name})");
            } catch (\Exception $e) {
                $this->command->error("为用户 {$email} 分配角色 '{$roleDisplayName}' 时出错: ".$e->getMessage());
            }
        } else {
            $this->command->warn("未找到测试用户 {$email}，跳过角色分配。");
        }
    }

    /**
     * 获取权限配置数组。
     *
     * 此数组定义了应用程序中所有权限的逻辑名称及相关的元数据。
     * `display_name` 和 `description` 字段不会存储在数据库中，仅用于代码可读性和UI展示。
     * `group` 字段用于逻辑分组权限。
     *
     * @return array 包含所有权限定义的数组。
     */
    private function getPermissionsConfig(): array
    {
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
            ['name' => 'role.edit', 'display_name' => '编辑角色', 'group' => self::GROUP_ROLE, 'description' => '允许编辑现有角色的名称、描述和权限'],
            ['name' => 'role.delete', 'display_name' => '删除角色', 'group' => self::GROUP_ROLE, 'description' => '允许删除用户角色（系统角色除外）'],
            ['name' => 'user.view', 'display_name' => '查看用户', 'group' => self::GROUP_USER, 'description' => '允许查看系统中的所有用户列表'],
            ['name' => 'user.edit', 'display_name' => '编辑用户角色', 'group' => self::GROUP_USER, 'description' => '允许修改用户的角色分配'],
            ['name' => 'log.view', 'display_name' => '查看系统日志', 'group' => self::GROUP_LOG, 'description' => '允许查看系统的活动日志和操作记录'],
        ];
    }
}
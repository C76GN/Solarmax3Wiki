<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Seeder;
// Import Support Collection for intermediate use
use Illuminate\Support\Facades\DB; // Import Eloquent Collection for return type

class PermissionSeeder extends Seeder
{
    private const ROLE_ADMIN = 'admin';

    private const ROLE_EDITOR = 'editor';

    private const ROLE_RESOLVER = 'conflict_resolver'; // Define constant

    private const GROUP_ROLE = 'role';

    private const GROUP_USER = 'user';

    private const GROUP_LOG = 'log';

    private const GROUP_WIKI = 'wiki'; // Define constant for wiki group

    public function run(): void
    {
        $this->command->info('开始初始化系统权限与角色...');

        // Optionally clear tables for clean seeding (ensure you understand the implications)
        // DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        // DB::table('permission_role')->truncate();
        // DB::table('role_user')->truncate();
        // Permission::truncate(); // Careful, truncates the table
        // Role::truncate();       // Careful, truncates the table
        // DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $permissions = $this->createPermissions(); // Store created permissions (now returns Eloquent Collection)

        // Check if permissions were actually created/retrieved
        if ($permissions->isEmpty() && count($this->getPermissionsConfig()) > 0) {
            $this->command->error('权限未能成功创建或获取，请检查数据库和代码。');

            return; // Stop seeding if permissions failed
        }

        $adminRole = $this->createAdminRole($permissions); // Pass all permissions
        $editorRole = $this->createEditorRole($permissions);
        $resolverRole = $this->createConflictResolverRole($permissions);

        $this->assignRolesToTestUsers($adminRole, $editorRole, $resolverRole);

        $this->command->info('系统权限与角色初始化完成！');
    }

    // Modify return type hint and logic
    private function createPermissions(): EloquentCollection
    {
        $permissionsConfig = $this->getPermissionsConfig();
        $count = 0;
        $createdOrUpdatedIds = []; // Store IDs of created/updated permissions

        foreach ($permissionsConfig as $permissionData) {
            // Use updateOrCreate to find or create the permission
            $permission = Permission::updateOrCreate(
                ['name' => $permissionData['name']], // Unique key to find by
                $permissionData                 // Data to update or insert
            );
            $createdOrUpdatedIds[] = $permission->id; // Collect the ID
            $count++;
        }

        $this->command->info("共创建/更新了 {$count} 个权限点");

        // After loop, fetch all created/updated permissions as an Eloquent Collection
        return Permission::whereIn('id', $createdOrUpdatedIds)->get();
    }

    // Accept Eloquent Collection type hint
    private function createAdminRole(EloquentCollection $permissions): Role
    {
        $adminRole = Role::firstOrCreate(
            ['name' => self::ROLE_ADMIN],
            [
                'display_name' => '管理员',
                'description' => '系统管理员，拥有所有权限',
                'is_system' => true,
            ]
        );
        // Sync all permission IDs from the Eloquent Collection
        $adminRole->permissions()->sync($permissions->pluck('id'));
        $this->command->info('管理员角色创建/更新完成，已分配所有权限');

        return $adminRole;
    }

    // Accept Eloquent Collection type hint
    private function createEditorRole(EloquentCollection $permissions): Role
    {
        $editorRole = Role::firstOrCreate(
            ['name' => self::ROLE_EDITOR],
            [
                'display_name' => '编辑',
                'description' => '内容编辑者，可以创建、编辑、评论页面',
                'is_system' => false,
            ]
        );
        $editorPermissionNames = [
            'wiki.view',
            'wiki.create',
            'wiki.edit',
            'wiki.comment',
        ];
        // Filter permissions by name and get IDs
        $permissionIds = $permissions->whereIn('name', $editorPermissionNames)->pluck('id');
        $editorRole->permissions()->sync($permissionIds);

        $this->command->info('编辑角色创建/更新完成，已分配相关权限');

        return $editorRole;
    }

    // Accept Eloquent Collection type hint
    private function createConflictResolverRole(EloquentCollection $permissions): Role
    {
        $resolverRole = Role::firstOrCreate(
            ['name' => self::ROLE_RESOLVER],
            [
                'display_name' => '冲突解决者',
                'description' => '具有解决Wiki页面编辑冲突的权限',
                'is_system' => false,
            ]
        );
        $resolverPermissionNames = [
            'wiki.view',
            'wiki.resolve_conflict',
            'wiki.history', // Allow viewing history to help resolve
            // Consider if wiki.edit is strictly needed or just resolve permission
        ];
        // Filter permissions by name and get IDs
        $permissionIds = $permissions->whereIn('name', $resolverPermissionNames)->pluck('id');
        $resolverRole->permissions()->sync($permissionIds);

        $this->command->info('冲突解决者角色创建/更新完成，已分配相关权限');

        return $resolverRole;
    }

    private function assignRolesToTestUsers(Role $adminRole, Role $editorRole, Role $resolverRole): void
    {
        $this->assignRoleToUser('admin@example.com', $adminRole, '管理员');
        $this->assignRoleToUser('editor@example.com', $editorRole, '编辑');
        $this->assignRoleToUser('resolver@example.com', $resolverRole, '冲突解决者');

        $user = User::where('email', 'user@example.com')->first();
        if ($user) {
            // Ensure regular user has NO special roles initially from this seeder
            if ($user->roles()->whereIn('name', [self::ROLE_ADMIN, self::ROLE_EDITOR, self::ROLE_RESOLVER])->exists()) {
                $user->roles()->detach(); // Or only detach the specific roles
                $this->command->info('重置了用户 user@example.com 的角色为空 (确保为普通用户)');
            } else {
                $this->command->info('用户 user@example.com (普通用户) 角色保持不变');
            }
        } else {
            $this->command->warn('未找到测试用户 user@example.com');
        }
    }

    // Helper method for assigning roles
    private function assignRoleToUser(string $email, Role $role, string $roleDisplayName): void
    {
        $user = User::where('email', $email)->first();
        if ($user) {
            // Use sync to ensure only this role is assigned (or syncWithoutDetaching if roles should be additive)
            $user->roles()->sync([$role->id]);
            $this->command->info("已为用户 {$email} 分配 '{$roleDisplayName}' 角色");
        } else {
            $this->command->warn("未找到测试用户 {$email}，跳过角色分配");
        }
    }

    private function getPermissionsConfig(): array
    {
        // Define permissions with groups
        return [
            // Wiki Group
            ['name' => 'wiki.view', 'display_name' => '查看Wiki页面', 'group' => self::GROUP_WIKI, 'description' => '允许查看所有公开的Wiki页面'],
            ['name' => 'wiki.create', 'display_name' => '创建Wiki页面', 'group' => self::GROUP_WIKI, 'description' => '允许创建新的Wiki页面'],
            ['name' => 'wiki.edit', 'display_name' => '编辑Wiki页面', 'group' => self::GROUP_WIKI, 'description' => '允许编辑自己或他人创建的Wiki页面'],
            ['name' => 'wiki.delete', 'display_name' => '删除Wiki页面', 'group' => self::GROUP_WIKI, 'description' => '允许删除Wiki页面'],
            ['name' => 'wiki.history', 'display_name' => '查看Wiki历史', 'group' => self::GROUP_WIKI, 'description' => '允许查看页面的编辑历史和版本差异'],
            ['name' => 'wiki.revert', 'display_name' => '恢复Wiki版本', 'group' => self::GROUP_WIKI, 'description' => '允许将页面恢复到指定的历史版本'],
            ['name' => 'wiki.comment', 'display_name' => '评论Wiki页面', 'group' => self::GROUP_WIKI, 'description' => '允许在Wiki页面下发表评论和回复'],
            ['name' => 'wiki.resolve_conflict', 'display_name' => '解决Wiki冲突', 'group' => self::GROUP_WIKI, 'description' => '允许访问冲突解决界面并合并/选择冲突版本'],
            ['name' => 'wiki.manage_categories', 'display_name' => '管理Wiki分类', 'group' => self::GROUP_WIKI, 'description' => '允许创建、编辑、删除Wiki分类'],
            ['name' => 'wiki.manage_tags', 'display_name' => '管理Wiki标签', 'group' => self::GROUP_WIKI, 'description' => '允许创建、编辑、删除Wiki标签'],
            ['name' => 'wiki.moderate_comments', 'display_name' => '管理Wiki评论', 'group' => self::GROUP_WIKI, 'description' => '允许编辑或删除他人的评论'],

            // Role Management Group
            ['name' => 'role.view', 'display_name' => '查看角色', 'group' => self::GROUP_ROLE, 'description' => '允许查看系统中的所有角色及其权限'],
            ['name' => 'role.create', 'display_name' => '创建角色', 'group' => self::GROUP_ROLE, 'description' => '允许创建新的用户角色'],
            ['name' => 'role.edit', 'display_name' => '编辑角色', 'group' => self::GROUP_ROLE, 'description' => '允许编辑现有角色的名称、描述和权限'],
            ['name' => 'role.delete', 'display_name' => '删除角色', 'group' => self::GROUP_ROLE, 'description' => '允许删除用户角色（系统角色除外）'],

            // User Management Group
            ['name' => 'user.view', 'display_name' => '查看用户', 'group' => self::GROUP_USER, 'description' => '允许查看系统中的所有用户列表'],
            ['name' => 'user.edit', 'display_name' => '编辑用户角色', 'group' => self::GROUP_USER, 'description' => '允许修改用户的角色分配'],
            // ['name' => 'user.manage', 'display_name' => '管理用户', 'group' => self::GROUP_USER, 'description' => '允许更全面的用户管理，如禁用、删除等'],

            // Log Group
            ['name' => 'log.view', 'display_name' => '查看系统日志', 'group' => self::GROUP_LOG, 'description' => '允许查看系统的活动日志和操作记录'],
        ];
    }
}

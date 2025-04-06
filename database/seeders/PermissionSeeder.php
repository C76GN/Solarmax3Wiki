<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;

class PermissionSeeder extends Seeder
{
    private const ROLE_ADMIN = 'admin';
    private const ROLE_EDITOR = 'editor';
    private const GROUP_ROLE = 'role';
    private const GROUP_USER = 'user';
    private const GROUP_LOG = 'log';

    public function run(): void
    {
        $this->command->info('开始初始化系统权限与角色...');
        
        $this->createPermissions();
        $adminRole = $this->createAdminRole();
        $editorRole = $this->createEditorRole();
        $resolverRole = $this->createConflictResolverRole(); // 添加这一行
        
        $this->assignRoleToTestUser($adminRole);
        
        $this->command->info('系统权限与角色初始化完成！');
    }

    private function createPermissions(): void
    {
        $permissions = $this->getPermissionsConfig();
        $count = 0;
        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name']],
                $permission
            );
            $count++;
        }
        $this->command->info("共创建了 {$count} 个权限点");
    }

    private function createAdminRole(): Role
    {
        $adminRole = Role::firstOrCreate(
            ['name' => self::ROLE_ADMIN],
            [
                'display_name' => '管理员',
                'description' => '系统管理员',
                'is_system' => true,
            ]
        );
        $adminRole->permissions()->sync(Permission::all());
        $this->command->info('管理员角色创建完成，已分配所有权限');
        return $adminRole;
    }

    private function createEditorRole(): Role
    {
        $editorRole = Role::firstOrCreate(
            ['name' => self::ROLE_EDITOR],
            [
                'display_name' => '编辑',
                'description' => '内容编辑',
                'is_system' => false,
            ]
        );
        
        // 编辑角色的权限
        $editorPermissions = [
            // 添加Wiki相关权限
            'wiki.view',
            'wiki.create',
            'wiki.edit',
            'wiki.comment'
        ];
        
        $editorRole->permissions()->sync(
            Permission::whereIn('name', $editorPermissions)->get()
        );
        
        $this->command->info('编辑角色创建完成，已分配相关权限');
        return $editorRole;
    }

    private function createConflictResolverRole(): Role
    {
        $resolverRole = Role::firstOrCreate(
            ['name' => 'conflict_resolver'],
            [
                'display_name' => '冲突解决者',
                'description' => '具有解决Wiki页面编辑冲突的权限',
                'is_system' => false,
            ]
        );
        
        // 冲突解决者角色的权限
        $resolverPermissions = [
            'wiki.view',
            'wiki.edit',
            'wiki.resolve_conflict'
        ];
        
        $resolverRole->permissions()->sync(
            Permission::whereIn('name', $resolverPermissions)->get()
        );
        
        $this->command->info('冲突解决者角色创建完成，已分配相关权限');
        return $resolverRole;
    }
    private function assignRoleToTestUser(Role $adminRole): void
    {
        $user = User::where('email', 'test@example.com')->first();
        if ($user) {
            $user->roles()->sync([$adminRole->id]);
            $this->command->info("已为测试用户 ({$user->email}) 分配管理员角色");
        } else {
            $this->command->warn("未找到测试用户，跳过角色分配");
        }
    }

    private function getPermissionsConfig(): array
    {
        return [
            ['name' => 'role.view', 'display_name' => '查看角色', 'group' => self::GROUP_ROLE],
            ['name' => 'role.create', 'display_name' => '创建角色', 'group' => self::GROUP_ROLE],
            ['name' => 'role.edit', 'display_name' => '编辑角色', 'group' => self::GROUP_ROLE],
            ['name' => 'role.delete', 'display_name' => '删除角色', 'group' => self::GROUP_ROLE],
            ['name' => 'user.view', 'display_name' => '查看用户', 'group' => self::GROUP_USER],
            ['name' => 'user.edit', 'display_name' => '编辑用户角色', 'group' => self::GROUP_USER],
            ['name' => 'log.view', 'display_name' => '查看系统日志', 'group' => self::GROUP_LOG],

            ['name' => 'wiki.view', 'display_name' => '查看Wiki页面', 'group' => 'wiki'],
            ['name' => 'wiki.create', 'display_name' => '创建Wiki页面', 'group' => 'wiki'],
            ['name' => 'wiki.edit', 'display_name' => '编辑Wiki页面', 'group' => 'wiki'],
            ['name' => 'wiki.delete', 'display_name' => '删除Wiki页面', 'group' => 'wiki'],
            ['name' => 'wiki.restore', 'display_name' => '恢复Wiki页面', 'group' => 'wiki'],
            ['name' => 'wiki.comment', 'display_name' => '评论Wiki页面', 'group' => 'wiki'],
            ['name' => 'wiki.resolve_conflict', 'display_name' => '解决Wiki冲突', 'group' => 'wiki'],
            ['name' => 'wiki.manage_categories', 'display_name' => '管理Wiki分类', 'group' => 'wiki'],
            ['name' => 'wiki.manage_tags', 'display_name' => '管理Wiki标签', 'group' => 'wiki'],
            ['name' => 'wiki.moderate_comments', 'display_name' => '管理Wiki评论', 'group' => 'wiki'],
            ['name' => 'wiki.manage_templates', 'display_name' => '管理Wiki模板', 'group' => 'wiki'],
        ];
    }
}
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;

/**
 * 系统权限与角色初始化
 * 
 * 该类负责初始化系统所需的所有权限、角色以及默认角色分配
 * 主要功能包括：
 * - 创建系统基础权限点
 * - 创建默认角色（管理员和编辑）
 * - 为角色分配对应权限
 * - 为默认测试用户分配管理员角色
 */
class PermissionSeeder extends Seeder
{
    /**
     * 系统角色常量
     */
    private const ROLE_ADMIN = 'admin';
    private const ROLE_EDITOR = 'editor';
    
    /**
     * 权限分组常量
     */
    private const GROUP_WIKI = 'wiki';
    private const GROUP_ROLE = 'role';
    private const GROUP_USER = 'user';
    private const GROUP_LOG = 'log';
    
    /**
     * 执行权限与角色初始化
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('开始初始化系统权限与角色...');
        
        // 创建系统基础权限
        $this->createPermissions();
        
        // 创建并配置默认角色
        $adminRole = $this->createAdminRole();
        $editorRole = $this->createEditorRole();
        
        // 为测试用户分配管理员角色
        $this->assignRoleToTestUser($adminRole);
        
        $this->command->info('系统权限与角色初始化完成！');
    }
    
    /**
     * 创建系统所需的所有权限点
     *
     * @return void
     */
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
    
    /**
     * 创建管理员角色并分配全部权限
     *
     * @return Role
     */
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
        
        // 为管理员角色分配所有权限
        $adminRole->permissions()->sync(Permission::all());
        
        $this->command->info('管理员角色创建完成，已分配所有权限');
        
        return $adminRole;
    }
    
    /**
     * 创建编辑角色并分配相应权限
     *
     * @return Role
     */
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
        
        // 为编辑角色分配指定权限
        $editorPermissions = [
            'wiki.view',
            'wiki.create',
            'wiki.edit',
            'wiki.publish'
        ];
        
        $editorRole->permissions()->sync(
            Permission::whereIn('name', $editorPermissions)->get()
        );
        
        $this->command->info('编辑角色创建完成，已分配Wiki编辑相关权限');
        
        return $editorRole;
    }
    
    /**
     * 为测试用户分配管理员角色
     *
     * @param Role $adminRole 管理员角色实例
     * @return void
     */
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
    
    /**
     * 获取系统权限配置
     *
     * @return array 权限配置数组
     */
    private function getPermissionsConfig(): array
    {
        return [
            // Wiki页面权限
            ['name' => 'wiki.view', 'display_name' => '查看Wiki页面', 'group' => self::GROUP_WIKI],
            ['name' => 'wiki.create', 'display_name' => '创建Wiki页面', 'group' => self::GROUP_WIKI],
            ['name' => 'wiki.edit', 'display_name' => '编辑Wiki页面', 'group' => self::GROUP_WIKI],
            ['name' => 'wiki.delete', 'display_name' => '删除Wiki页面', 'group' => self::GROUP_WIKI],
            ['name' => 'wiki.publish', 'display_name' => '发布Wiki页面', 'group' => self::GROUP_WIKI],
            ['name' => 'wiki.manage_trash', 'display_name' => '管理回收站', 'group' => self::GROUP_WIKI],
            ['name' => 'wiki.unlock_any', 'display_name' => '解锁任何页面', 'group' => self::GROUP_WIKI],
            ['name' => 'wiki.issue', 'display_name' => '报告页面问题', 'group' => self::GROUP_WIKI],
            ['name' => 'wiki.issue_handle', 'display_name' => '处理页面问题', 'group' => self::GROUP_WIKI],
            
            // Wiki分类权限
            ['name' => 'wiki.category.view', 'display_name' => '查看Wiki分类', 'group' => self::GROUP_WIKI],
            ['name' => 'wiki.category.create', 'display_name' => '创建Wiki分类', 'group' => self::GROUP_WIKI],
            ['name' => 'wiki.category.edit', 'display_name' => '编辑Wiki分类', 'group' => self::GROUP_WIKI],
            ['name' => 'wiki.category.delete', 'display_name' => '删除Wiki分类', 'group' => self::GROUP_WIKI],
            
            // 角色管理权限
            ['name' => 'role.view', 'display_name' => '查看角色', 'group' => self::GROUP_ROLE],
            ['name' => 'role.create', 'display_name' => '创建角色', 'group' => self::GROUP_ROLE],
            ['name' => 'role.edit', 'display_name' => '编辑角色', 'group' => self::GROUP_ROLE],
            ['name' => 'role.delete', 'display_name' => '删除角色', 'group' => self::GROUP_ROLE],
            
            // 用户管理权限
            ['name' => 'user.view', 'display_name' => '查看用户', 'group' => self::GROUP_USER],
            ['name' => 'user.edit', 'display_name' => '编辑用户角色', 'group' => self::GROUP_USER],
            
            // 日志查看权限
            ['name' => 'log.view', 'display_name' => '查看系统日志', 'group' => self::GROUP_LOG],
        ];
    }
}
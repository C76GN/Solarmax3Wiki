<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * 数据库种子类
 * 
 * 该类负责协调所有数据库种子的执行顺序，确保数据依赖关系得到正确处理
 * 主要功能包括：
 * - 初始化系统必要的权限和角色数据
 * - 创建默认的测试用户账号
 * - 可选：填充示例数据用于开发和测试环境
 */
class DatabaseSeeder extends Seeder
{
    /**
     * 执行数据库填充
     * 
     * 按照依赖关系顺序执行各个Seeder：
     * 1. 首先填充权限和角色相关数据
     * 2. 然后创建默认测试用户
     * 
     * @return void
     */
    public function run(): void
    {
        // 填充权限和角色数据
        $this->call([
            PermissionSeeder::class,
        ]);
        
        // 创建默认测试用户
        $this->createDefaultUser();
    }
    
    /**
     * 创建默认测试用户
     * 
     * 为系统创建一个默认的测试用户，方便开发和测试
     * 
     * @return void
     */
    private function createDefaultUser(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            // 密码默认为 'password'，由 UserFactory 设置
        ]);
        
        $this->command->info('默认测试用户已创建');
    }
}
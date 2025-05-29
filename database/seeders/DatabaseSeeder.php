<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * 基础数据库填充器。
 *
 * 用于在应用程序启动时填充初始数据，例如测试用户和权限。
 */
class DatabaseSeeder extends Seeder
{
    /**
     * 运行数据库填充。
     *
     * 依次调用测试用户创建和权限填充器。
     */
    public function run(): void
    {
        $this->createTestUsers(); // 创建测试用户

        $this->call([
            PermissionSeeder::class, // 初始化权限和角色
        ]);
    }

    /**
     * 创建不同类型的测试用户。
     */
    private function createTestUsers(): void
    {
        // 创建管理员用户
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // 创建编辑者用户
        User::factory()->create([
            'name' => 'Editor User',
            'email' => 'editor@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // 创建冲突解决者用户
        User::factory()->create([
            'name' => 'Resolver User',
            'email' => 'resolver@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // 创建普通用户
        User::factory()->create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // 在控制台输出创建成功的提示信息
        $this->command->info('测试用户已创建: admin@example.com, editor@example.com, resolver@example.com, user@example.com');
    }
}
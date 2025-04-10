<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 创建各类测试用户
        $this->createTestUsers();

        // 初始化权限和角色
        $this->call([
            PermissionSeeder::class,
        ]);
    }

    private function createTestUsers(): void
    {
        // 创建默认管理员用户
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

        $this->command->info('测试用户已创建: admin@example.com, editor@example.com, resolver@example.com, user@example.com');
    }
}

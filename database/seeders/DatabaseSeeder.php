<?php
namespace Database\Seeders;
use App\Models\User;
use Illuminate\Database\Seeder;
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 先创建默认用户
        $this->createDefaultUser();
        
        // 然后初始化权限和角色
        $this->call([
            PermissionSeeder::class,
        ]);
    }
    
    private function createDefaultUser(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        $this->command->info('默认测试用户已创建');
    }
}
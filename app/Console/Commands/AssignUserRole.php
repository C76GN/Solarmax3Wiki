<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Role;
use Illuminate\Console\Command;

class AssignUserRole extends Command
{
    protected $signature = 'user:role {email} {role=admin}';

    protected $description = '为用户分配角色';

    public function handle()
    {
        $email = $this->argument('email');
        $roleName = $this->argument('role');

        $user = User::where('email', $email)->first();
        if (!$user) {
            $this->error("未找到邮箱为 {$email} 的用户");
            return 1;
        }

        $role = Role::where('name', $roleName)->first();
        if (!$role) {
            $this->error("未找到角色 {$roleName}");
            return 1;
        }

        $user->roles()->sync([$role->id]);

        $this->info("已成功为用户 {$email} 分配角色 {$roleName}");
        return 0;
    }
}
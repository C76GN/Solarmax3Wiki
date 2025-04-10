<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * 用户角色分配命令
 *
 * 此命令用于为指定邮箱的用户分配指定的角色权限
 */
class AssignUserRole extends Command
{
    /**
     * 命令名称及参数定义
     *
     * @var string
     */
    protected $signature = 'user:role {email : 用户邮箱地址} {role=admin : 要分配的角色名称，默认为admin}';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '为指定用户分配系统角色';

    /**
     * 执行命令
     *
     * @return int 返回状态码，0表示成功，非0表示失败
     */
    public function handle(): int
    {
        // 获取命令参数
        $email = $this->argument('email');
        $roleName = $this->argument('role');

        // 查找用户
        $user = User::where('email', $email)->first();
        if (! $user) {
            $this->error("未找到邮箱为 {$email} 的用户");

            return 1;
        }

        // 查找角色
        $role = Role::where('name', $roleName)->first();
        if (! $role) {
            $this->error("未找到角色 {$roleName}");

            return 1;
        }

        // 分配角色（使用sync方法替换用户的所有角色）
        $user->roles()->sync([$role->id]);

        $this->info("已成功为用户 {$email} 分配角色 {$roleName}");

        return 0;
    }
}

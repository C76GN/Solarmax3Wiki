<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * 用户角色分配命令
 *
 * 此 Artisan 命令用于为指定邮箱的用户分配指定的角色权限。
 * 它通过命令行接收用户邮箱和角色名称作为参数。
 */
class AssignUserRole extends Command
{
    /**
     * Artisan 命令的签名及其参数定义。
     *
     * 定义了命令的调用方式、必填参数和可选参数的默认值。
     * - `email`: 用户的邮箱地址，一个必填参数。
     * - `role`: 要分配的角色名称，如果未指定，默认为 'admin'。
     *
     * @var string
     */
    protected $signature = 'user:role {email : 用户邮箱地址} {role=admin : 要分配的角色名称，默认为admin}';

    /**
     * Artisan 命令的简短描述。
     *
     * 当运行 `php artisan list` 时，此描述会显示在命令行工具中。
     *
     * @var string
     */
    protected $description = '为指定用户分配系统角色';

    /**
     * 执行 Artisan 命令的逻辑。
     *
     * 此方法包含命令的业务逻辑，负责获取参数、查找用户和角色，
     * 并执行角色分配操作，同时提供适当的命令行反馈。
     *
     * @return int 返回状态码：0 表示成功，非 0 表示失败。
     */
    public function handle(): int
    {
        // 获取命令行中传入的用户邮箱地址
        $email = $this->argument('email');
        // 获取命令行中传入的角色名称，若未指定则使用默认值 'admin'
        $roleName = $this->argument('role');

        // 根据邮箱地址查询用户
        $user = User::where('email', $email)->first();

        // 检查用户是否存在
        if (! $user) {
            // 如果未找到用户，输出错误信息到命令行
            $this->error("未找到邮箱为 {$email} 的用户");
            // 返回非零状态码，指示命令执行失败
            return 1;
        }

        // 根据角色名称查询角色
        $role = Role::where('name', $roleName)->first();

        // 检查角色是否存在
        if (! $role) {
            // 如果未找到角色，输出错误信息到命令行
            $this->error("未找到角色 {$roleName}");
            // 返回非零状态码，指示命令执行失败
            return 1;
        }

        // 为用户分配角色。
        // `sync()` 方法会移除用户所有现有角色，只保留传入的这一个角色ID。
        $user->roles()->sync([$role->id]);

        // 输出成功信息到命令行
        $this->info("已成功为用户 {$email} 分配角色 {$roleName}");

        // 返回零状态码，指示命令执行成功
        return 0;
    }
}
<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * 检查用户权限命令
 *
 * 此 Artisan 命令用于检查指定邮箱的用户是否拥有特定的系统权限。
 * 它通过命令行接收用户邮箱和权限名称作为参数，并根据检查结果输出相应信息。
 */
class CheckUserPermission extends Command
{
    /**
     * Artisan 命令的签名及其参数定义。
     *
     * 定义了命令的调用方式以及所需的参数：
     * - `email`: 用户的邮箱地址，一个必填参数。
     * - `permission`: 要检查的权限名称，一个必填参数。
     *
     * @var string
     */
    protected $signature = 'user:check-permission
                            {email : 用户的邮箱地址}
                            {permission : 要检查的权限名称}';

    /**
     * Artisan 命令的简短描述。
     *
     * 当运行 `php artisan list` 时，此描述会显示在命令行工具中。
     *
     * @var string
     */
    protected $description = '检查用户是否拥有指定的系统权限';

    /**
     * 执行 Artisan 命令的逻辑。
     *
     * 此方法包含命令的核心业务逻辑：
     * 1. 从命令行获取用户邮箱和权限名称。
     * 2. 根据邮箱查找用户。
     * 3. 如果用户不存在，输出错误并返回失败状态码。
     * 4. 检查用户是否拥有指定权限，并根据结果输出成功或失败信息。
     *
     * @return int 返回状态码：0 表示成功，非 0 表示失败。
     */
    public function handle(): int
    {
        // 获取命令行中传入的用户邮箱地址
        $email = $this->argument('email');
        // 获取命令行中传入的权限名称
        $permissionName = $this->argument('permission');

        // 根据邮箱地址查询用户
        $user = User::where('email', $email)->first();

        // 检查用户是否存在
        if (! $user) {
            // 如果未找到用户，输出错误信息到命令行
            $this->error("未找到邮箱为 {$email} 的用户");
            // 返回非零状态码，指示命令执行失败
            return 1;
        }

        // 调用用户模型的 `hasPermission()` 方法检查权限
        if ($user->hasPermission($permissionName)) {
            // 如果用户拥有该权限，输出成功信息
            $this->info("用户 {$email} 有权限 {$permissionName}");
        } else {
            // 如果用户没有该权限，输出错误信息
            $this->error("用户 {$email} 没有权限 {$permissionName}");
        }

        // 返回零状态码，指示命令执行完成（无论是否有权限，操作本身成功执行）
        return 0;
    }
}
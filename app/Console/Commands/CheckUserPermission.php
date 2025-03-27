<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * 检查用户权限命令
 * 
 * 此命令用于检查指定用户是否拥有特定权限
 */
class CheckUserPermission extends Command
{
    /**
     * 命令名称及参数定义
     *
     * @var string
     */
    protected $signature = 'user:check-permission 
                            {email : 用户的邮箱地址} 
                            {permission : 要检查的权限名称}';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '检查用户是否拥有指定的系统权限';

    /**
     * 执行命令
     *
     * @return int 返回状态码，0表示成功，非0表示失败
     */
    public function handle(): int
    {
        // 获取命令参数
        $email = $this->argument('email');
        $permissionName = $this->argument('permission');
        
        // 查找用户
        $user = User::where('email', $email)->first();
        if (!$user) {
            $this->error("未找到邮箱为 {$email} 的用户");
            return 1;
        }
        
        // 检查用户权限
        if ($user->hasPermission($permissionName)) {
            $this->info("用户 {$email} 有权限 {$permissionName}");
        } else {
            $this->error("用户 {$email} 没有权限 {$permissionName}");
        }
        
        return 0;
    }
}
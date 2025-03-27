<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/**
 * 控制台命令与计划任务配置
 * 
 * 本文件定义了:
 * 1. 自定义 Artisan 命令
 * 2. 系统计划任务配置
 */

/**
 * 自定义 Artisan 命令定义
 * 
 * 这些命令可以通过 `php artisan <命令名>` 执行
 */

// 显示激励语录的命令 (框架自带示例)
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})
->purpose('Display an inspiring quote') // 命令目的说明
->hourly(); // 设为每小时自动执行一次

/**
 * 系统计划任务配置
 * 
 * 这些任务会按照指定的计划自动执行
 * 需要在服务器上设置 Cron 以运行 `php artisan schedule:run`
 */

// Wiki回收站自动清理 - 清理超过30天的已删除页面
Schedule::command('wiki:cleanup-trash 30')
    ->weekly() // 每周执行一次
    ->sundays() // 具体在每周日执行
    ->at('01:00') // 凌晨1点执行
    ->description('每周清理回收站中超过30天的页面')
    ->onOneServer(); // 在多服务器环境中仅执行一次
<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule; // 确保引入 Schedule

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// 在这里定义你的计划任务
Schedule::call(function () {
    // 如果你想在这里执行更复杂的逻辑，也可以
    // 但通常推荐使用 Artisan 命令
})->daily();

// 添加清理回收站的计划任务
// 示例：每天凌晨 3 点清理超过 30 天的已删除页面
Schedule::command('wiki:purge-trash --days=30')->dailyAt('03:00'); // <--- 添加这行

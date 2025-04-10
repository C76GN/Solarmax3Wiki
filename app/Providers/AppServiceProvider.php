<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

/**
 * 应用服务提供者
 *
 * 负责注册应用程序的服务和启动时执行的操作
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * 注册应用服务
     */
    public function register(): void
    {
        // 在此处注册应用程序的服务绑定
    }

    /**
     * 引导应用服务
     */
    public function boot(): void
    {
        // 配置Vite资源预加载，设置并发数为3
        Vite::prefetch(concurrency: 3);
    }
}

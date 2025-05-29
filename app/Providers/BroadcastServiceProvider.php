<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

/**
 * 广播服务提供者。
 * 用于注册应用程序的广播频道。
 */
class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * 引导任何应用程序服务。
     *
     * 此方法在应用程序启动时运行，用于注册广播路由和加载频道定义。
     */
    public function boot(): void
    {
        // 注册广播路由，使应用程序可以处理广播请求。
        Broadcast::routes();

        // 引入定义了所有广播频道的文件。
        require base_path('routes/channels.php');
    }
}
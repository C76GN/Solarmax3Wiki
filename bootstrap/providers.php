<?php

/**
 * 应用程序服务提供者配置文件
 *
 * 这个文件定义了应用程序启动时需要加载的服务提供者类列表。
 * 服务提供者负责引导和配置应用程序的各种组件。
 */
return [
    // 核心应用服务提供者
    App\Providers\AppServiceProvider::class,
    App\Providers\BroadcastServiceProvider::class,
    App\Providers\AuthServiceProvider::class,
    // 此处可以添加更多自定义服务提供者
];

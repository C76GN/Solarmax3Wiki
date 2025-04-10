<?php

/**
 * 文件系统配置
 *
 * 此文件定义应用程序的文件存储设置，包括默认存储磁盘、
 * 可用的存储驱动及其配置选项，以及公共存储的符号链接。
 */
return [
    /**
     * 默认文件系统磁盘
     *
     * 指定应用程序默认使用的存储磁盘。
     * 可选值包括: "local", "public", "s3"等自定义磁盘
     */
    'default' => env('FILESYSTEM_DISK', 'local'),

    /**
     * 文件系统磁盘配置
     *
     * 定义可用的存储磁盘及其详细配置。
     */
    'disks' => [
        // 本地私有存储
        'local' => [
            'driver' => 'local',               // 本地文件系统驱动
            'root' => storage_path('app/private'), // 存储根目录
            'serve' => true,                   // 启用直接访问
            'throw' => false,                  // 是否抛出异常
        ],

        // 本地公共存储
        'public' => [
            'driver' => 'local',               // 本地文件系统驱动
            'root' => storage_path('app/public'),  // 存储根目录
            'url' => env('APP_URL').'/storage',    // 访问URL
            'visibility' => 'public',          // 文件可见性
            'throw' => false,                  // 是否抛出异常
        ],

        // Amazon S3云存储
        's3' => [
            'driver' => 's3',                  // S3驱动
            'key' => env('AWS_ACCESS_KEY_ID'),      // AWS访问密钥
            'secret' => env('AWS_SECRET_ACCESS_KEY'), // AWS密钥
            'region' => env('AWS_DEFAULT_REGION'),    // AWS区域
            'bucket' => env('AWS_BUCKET'),           // S3存储桶
            'url' => env('AWS_URL'),                 // 自定义URL
            'endpoint' => env('AWS_ENDPOINT'),       // 自定义终端节点
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false), // 路径样式终端节点
            'throw' => false,                  // 是否抛出异常
        ],
    ],

    /**
     * 符号链接
     *
     * 将公共目录链接到存储目录的映射关系，用于访问公共存储的文件。
     * 可以通过运行 `php artisan storage:link` 命令创建这些符号链接。
     */
    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],
];

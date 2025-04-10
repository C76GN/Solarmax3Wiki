<?php

use Illuminate\Support\Str;

/**
 * 缓存配置
 *
 * 这个文件定义了应用程序的缓存配置，包括默认缓存驱动和各种
 * 缓存存储方式的配置选项。
 */
return [
    /**
     * 默认缓存存储
     *
     * 指定应用程序使用的默认缓存驱动。
     * 可选值包括: "array", "database", "file", "memcached", "redis", "dynamodb", "octane"
     */
    'default' => env('CACHE_STORE', 'database'),

    /**
     * 缓存存储配置
     *
     * 定义可用的缓存驱动及其配置选项。
     */
    'stores' => [
        // 数组缓存 - 仅在请求生命周期内有效，适用于测试
        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],

        // 数据库缓存 - 将缓存数据存储在数据库中
        'database' => [
            'driver' => 'database',
            'connection' => env('DB_CACHE_CONNECTION'),           // 数据库连接
            'table' => env('DB_CACHE_TABLE', 'cache'),            // 缓存表名
            'lock_connection' => env('DB_CACHE_LOCK_CONNECTION'), // 锁连接
            'lock_table' => env('DB_CACHE_LOCK_TABLE'),           // 锁表名
        ],

        // 文件缓存 - 将缓存数据存储在文件系统中
        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),       // 缓存文件存储路径
            'lock_path' => storage_path('framework/cache/data'),  // 锁文件存储路径
        ],

        // Memcached缓存 - 使用Memcached服务
        'memcached' => [
            'driver' => 'memcached',
            'persistent_id' => env('MEMCACHED_PERSISTENT_ID'),    // 持久连接ID
            'sasl' => [                                           // 安全认证
                env('MEMCACHED_USERNAME'),
                env('MEMCACHED_PASSWORD'),
            ],
            'options' => [],                                      // 附加选项
            'servers' => [                                        // 服务器配置
                [
                    'host' => env('MEMCACHED_HOST', '127.0.0.1'),
                    'port' => env('MEMCACHED_PORT', 11211),
                    'weight' => 100,
                ],
            ],
        ],

        // Redis缓存 - 使用Redis服务
        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_CACHE_CONNECTION', 'cache'),         // Redis连接
            'lock_connection' => env('REDIS_CACHE_LOCK_CONNECTION', 'default'), // 锁连接
        ],

        // DynamoDB缓存 - 使用AWS DynamoDB服务
        'dynamodb' => [
            'driver' => 'dynamodb',
            'key' => env('AWS_ACCESS_KEY_ID'),                    // AWS访问密钥
            'secret' => env('AWS_SECRET_ACCESS_KEY'),             // AWS密钥
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),   // AWS区域
            'table' => env('DYNAMODB_CACHE_TABLE', 'cache'),      // DynamoDB表名
            'endpoint' => env('DYNAMODB_ENDPOINT'),               // 自定义终端节点
        ],

        // Octane缓存 - 用于Laravel Octane
        'octane' => [
            'driver' => 'octane',
        ],
    ],

    /**
     * 缓存键前缀
     *
     * 为所有缓存键添加前缀，避免键名冲突。
     */
    'prefix' => env('CACHE_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_cache_'),
];

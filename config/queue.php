<?php

/**
 * 队列配置
 *
 * 此文件定义应用程序的队列系统配置，包括默认队列连接、
 * 各种队列驱动的配置选项、批处理设置和失败任务处理。
 */
return [
    /**
     * 默认队列连接
     *
     * 指定应用程序默认使用的队列连接。
     */
    'default' => env('QUEUE_CONNECTION', 'database'),

    /**
     * 队列连接配置
     *
     * 定义可用的队列连接及其详细配置。
     */
    'connections' => [
        // 同步队列 - 直接执行任务，不入队列（用于本地开发）
        'sync' => [
            'driver' => 'sync',
        ],

        // 数据库队列 - 使用数据库存储队列任务
        'database' => [
            'driver' => 'database',
            'connection' => env('DB_QUEUE_CONNECTION'),           // 数据库连接
            'table' => env('DB_QUEUE_TABLE', 'jobs'),             // 存储任务的表
            'queue' => env('DB_QUEUE', 'default'),                // 默认队列名称
            'retry_after' => (int) env('DB_QUEUE_RETRY_AFTER', 90), // 重试间隔（秒）
            'after_commit' => false,                              // 事务提交后执行
        ],

        // Beanstalkd队列
        'beanstalkd' => [
            'driver' => 'beanstalkd',
            'host' => env('BEANSTALKD_QUEUE_HOST', 'localhost'),  // 服务器地址
            'queue' => env('BEANSTALKD_QUEUE', 'default'),        // 默认队列名称
            'retry_after' => (int) env('BEANSTALKD_QUEUE_RETRY_AFTER', 90), // 重试间隔（秒）
            'block_for' => 0,                                     // 工作进程等待时间
            'after_commit' => false,                              // 事务提交后执行
        ],

        // Amazon SQS队列
        'sqs' => [
            'driver' => 'sqs',
            'key' => env('AWS_ACCESS_KEY_ID'),                    // AWS访问密钥
            'secret' => env('AWS_SECRET_ACCESS_KEY'),             // AWS密钥
            'prefix' => env('SQS_PREFIX', 'https://sqs.us-east-1.amazonaws.com/your-account-id'),
            'queue' => env('SQS_QUEUE', 'default'),               // 默认队列名称
            'suffix' => env('SQS_SUFFIX'),                        // 队列后缀
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),   // AWS区域
            'after_commit' => false,                              // 事务提交后执行
        ],

        // Redis队列
        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_QUEUE_CONNECTION', 'default'), // Redis连接
            'queue' => env('REDIS_QUEUE', 'default'),             // 默认队列名称
            'retry_after' => (int) env('REDIS_QUEUE_RETRY_AFTER', 90), // 重试间隔（秒）
            'block_for' => null,                                  // 工作进程等待时间
            'after_commit' => false,                              // 事务提交后执行
        ],
    ],

    /**
     * 批处理配置
     *
     * 用于存储和跟踪任务批处理的设置。
     */
    'batching' => [
        'database' => env('DB_CONNECTION', 'sqlite'),             // 使用的数据库连接
        'table' => 'job_batches',                                 // 存储批处理的表
    ],

    /**
     * 失败任务处理
     *
     * 控制失败任务的存储和管理方式。
     */
    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'), // 失败任务驱动
        'database' => env('DB_CONNECTION', 'sqlite'),             // 使用的数据库连接
        'table' => 'failed_jobs',                                 // 存储失败任务的表
    ],
];

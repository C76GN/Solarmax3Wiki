<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Processor\PsrLogMessageProcessor;

/**
* 日志配置
*
* 此文件定义应用程序的日志系统配置，包括默认日志通道、
* 弃用日志设置以及各种可用的日志通道配置。
*/
return [
   /**
    * 默认日志通道
    *
    * 指定应用程序默认使用的日志通道。
    */
   'default' => env('LOG_CHANNEL', 'stack'),

   /**
    * 弃用警告日志设置
    *
    * 控制Laravel弃用警告的日志记录方式。
    */
   'deprecations' => [
       'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'), // 弃用警告使用的通道
       'trace' => env('LOG_DEPRECATIONS_TRACE', false),      // 是否包含堆栈跟踪
   ],

   /**
    * 日志通道配置
    *
    * 定义可用的日志通道及其详细配置。
    */
   'channels' => [
       // 堆栈通道 - 将日志发送到多个通道
       'stack' => [
           'driver' => 'stack',
           'channels' => explode(',', env('LOG_STACK', 'single')), // 使用的通道列表
           'ignore_exceptions' => false,                           // 是否忽略异常
       ],

       // 单文件通道 - 将所有日志写入单个文件
       'single' => [
           'driver' => 'single',
           'path' => storage_path('logs/laravel.log'),             // 日志文件路径
           'level' => env('LOG_LEVEL', 'debug'),                   // 日志级别
           'replace_placeholders' => true,                         // 替换日志中的占位符
       ],

       // 每日文件通道 - 每天创建一个新的日志文件
       'daily' => [
           'driver' => 'daily',
           'path' => storage_path('logs/laravel.log'),             // 日志文件路径
           'level' => env('LOG_LEVEL', 'debug'),                   // 日志级别
           'days' => env('LOG_DAILY_DAYS', 14),                    // 保留天数
           'replace_placeholders' => true,                         // 替换日志中的占位符
       ],

       // Slack通道 - 发送日志到Slack
       'slack' => [
           'driver' => 'slack',
           'url' => env('LOG_SLACK_WEBHOOK_URL'),                  // Slack Webhook URL
           'username' => env('LOG_SLACK_USERNAME', 'Laravel Log'), // 显示名称
           'emoji' => env('LOG_SLACK_EMOJI', ':boom:'),            // 显示图标
           'level' => env('LOG_LEVEL', 'critical'),                // 日志级别
           'replace_placeholders' => true,                         // 替换日志中的占位符
       ],

       // Papertrail通道 - 发送日志到Papertrail服务
       'papertrail' => [
           'driver' => 'monolog',
           'level' => env('LOG_LEVEL', 'debug'),                   // 日志级别
           'handler' => env('LOG_PAPERTRAIL_HANDLER', SyslogUdpHandler::class),
           'handler_with' => [
               'host' => env('PAPERTRAIL_URL'),
               'port' => env('PAPERTRAIL_PORT'),
               'connectionString' => 'tls://'.env('PAPERTRAIL_URL').':'.env('PAPERTRAIL_PORT'),
           ],
           'processors' => [PsrLogMessageProcessor::class],        // 日志处理器
       ],

       // 标准错误输出通道
       'stderr' => [
           'driver' => 'monolog',
           'level' => env('LOG_LEVEL', 'debug'),                   // 日志级别
           'handler' => StreamHandler::class,
           'formatter' => env('LOG_STDERR_FORMATTER'),
           'with' => [
               'stream' => 'php://stderr',                         // 输出到标准错误
           ],
           'processors' => [PsrLogMessageProcessor::class],        // 日志处理器
       ],

       // 系统日志通道
       'syslog' => [
           'driver' => 'syslog',
           'level' => env('LOG_LEVEL', 'debug'),                   // 日志级别
           'facility' => env('LOG_SYSLOG_FACILITY', LOG_USER),     // 系统日志设施
           'replace_placeholders' => true,                         // 替换日志中的占位符
       ],

       // 错误日志通道 - 使用PHP的error_log()函数
       'errorlog' => [
           'driver' => 'errorlog',
           'level' => env('LOG_LEVEL', 'debug'),                   // 日志级别
           'replace_placeholders' => true,                         // 替换日志中的占位符
       ],

       // 空通道 - 不记录任何日志
       'null' => [
           'driver' => 'monolog',
           'handler' => NullHandler::class,                        // 空处理器
       ],

       // 紧急通道 - 用于记录紧急错误
       'emergency' => [
           'path' => storage_path('logs/laravel.log'),             // 日志文件路径
       ],
   ],
];
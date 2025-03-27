<?php

/**
* 邮件配置
*
* 此文件定义应用程序的邮件发送配置，包括默认邮件发送驱动、
* 可用的邮件发送驱动及其详细配置，以及默认发件人信息。
*/
return [
   /**
    * 默认邮件发送驱动
    *
    * 指定应用程序默认使用的邮件发送驱动。
    */
   'default' => env('MAIL_MAILER', 'log'),

   /**
    * 邮件发送驱动配置
    *
    * 定义可用的邮件发送驱动及其详细配置。
    */
   'mailers' => [
       // SMTP驱动 - 通过SMTP服务器发送邮件
       'smtp' => [
           'transport' => 'smtp',
           'url' => env('MAIL_URL'),                               // 完整的SMTP服务器URL
           'host' => env('MAIL_HOST', '127.0.0.1'),                // SMTP服务器地址
           'port' => env('MAIL_PORT', 2525),                       // SMTP服务器端口
           'encryption' => env('MAIL_ENCRYPTION', 'tls'),          // 加密方式
           'username' => env('MAIL_USERNAME'),                     // SMTP用户名
           'password' => env('MAIL_PASSWORD'),                     // SMTP密码
           'timeout' => null,                                      // 连接超时时间
           'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
       ],

       // Amazon SES驱动
       'ses' => [
           'transport' => 'ses',
       ],

       // Postmark驱动
       'postmark' => [
           'transport' => 'postmark',
       ],

       // Resend驱动
       'resend' => [
           'transport' => 'resend',
       ],

       // Sendmail驱动 - 使用本地Sendmail程序
       'sendmail' => [
           'transport' => 'sendmail',
           'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'), // Sendmail路径
       ],

       // 日志驱动 - 将邮件写入日志文件
       'log' => [
           'transport' => 'log',
           'channel' => env('MAIL_LOG_CHANNEL'),                   // 使用的日志通道
       ],

       // 数组驱动 - 将邮件存储在数组中（用于测试）
       'array' => [
           'transport' => 'array',
       ],

       // 故障转移驱动 - 按顺序尝试多个邮件发送驱动
       'failover' => [
           'transport' => 'failover',
           'mailers' => [                                          // 要尝试的邮件发送驱动
               'smtp',
               'log',
           ],
       ],

       // 轮询驱动 - 轮流使用多个邮件发送驱动
       'roundrobin' => [
           'transport' => 'roundrobin',
           'mailers' => [                                          // 要轮流使用的邮件发送驱动
               'ses',
               'postmark',
           ],
       ],
   ],

   /**
    * 全局发件人
    *
    * 发送邮件时使用的默认发件人地址和名称。
    */
   'from' => [
       'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'), // 发件人邮箱地址
       'name' => env('MAIL_FROM_NAME', 'Example'),                // 发件人名称
   ],
];
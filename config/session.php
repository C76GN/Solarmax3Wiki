<?php

use Illuminate\Support\Str;

/**
* 会话配置
*
* 此文件定义应用程序的会话处理配置，包括会话存储驱动、
* 会话生命周期、Cookie设置和安全选项。
*/
return [
   /**
    * 会话存储驱动
    *
    * 支持的驱动: "file", "cookie", "database", "apc",
    * "memcached", "redis", "dynamodb", "array"
    */
   'driver' => env('SESSION_DRIVER', 'database'),

   /**
    * 会话生命周期
    *
    * 会话过期的分钟数。设置为 0 表示会话将永久有效。
    */
   'lifetime' => env('SESSION_LIFETIME', 120),

   /**
    * 关闭浏览器时过期
    *
    * 当设置为 true 时，关闭浏览器会使会话过期。
    */
   'expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE', false),

   /**
    * 加密会话
    *
    * 当设置为 true 时，会话数据将被加密。
    */
   'encrypt' => env('SESSION_ENCRYPT', false),

   /**
    * 文件存储位置
    *
    * 使用文件驱动时存储会话文件的位置。
    */
   'files' => storage_path('framework/sessions'),

   /**
    * 数据库连接
    *
    * 使用数据库驱动时，指定要使用的数据库连接。
    */
   'connection' => env('SESSION_CONNECTION'),

   /**
    * 会话表名
    *
    * 使用数据库驱动时，指定存储会话的表名。
    */
   'table' => env('SESSION_TABLE', 'sessions'),

   /**
    * 会话存储器
    *
    * 使用自定义存储驱动时指定。
    */
   'store' => env('SESSION_STORE'),

   /**
    * 会话垃圾回收概率
    *
    * 定义垃圾回收的概率，格式为 [x, y]，表示 y 分之 x 的概率进行回收。
    */
   'lottery' => [2, 100],

   /**
    * 会话 Cookie 名称
    *
    * 会话 Cookie 的名称，默认使用应用名称加上 "_session" 后缀。
    */
   'cookie' => env(
       'SESSION_COOKIE',
       Str::slug(env('APP_NAME', 'laravel'), '_').'_session'
   ),

   /**
    * 会话 Cookie 路径
    *
    * Cookie 可用的路径，默认为根路径 "/"。
    */
   'path' => env('SESSION_PATH', '/'),

   /**
    * 会话 Cookie 域名
    *
    * Cookie 可用的域名。
    */
   'domain' => env('SESSION_DOMAIN'),

   /**
    * HTTPS 专用
    *
    * 如果设置为 true，Cookie 只能通过 HTTPS 传输。
    */
   'secure' => env('SESSION_SECURE_COOKIE'),

   /**
    * HTTP 专用
    *
    * 如果设置为 true，JavaScript 无法访问 Cookie。
    */
   'http_only' => env('SESSION_HTTP_ONLY', true),

   /**
    * Same-Site Cookie
    *
    * 控制 Cookie 的 Same-Site 属性。可选值: lax, strict, none。
    */
   'same_site' => env('SESSION_SAME_SITE', 'lax'),

   /**
    * 分区 Cookie
    *
    * 是否启用 Cookie 分区。
    */
   'partitioned' => env('SESSION_PARTITIONED_COOKIE', false),
];
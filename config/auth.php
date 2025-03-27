<?php

/**
* 认证配置
*
* 这个文件包含应用程序认证系统的配置，包括认证方式（guard）、
* 用户提供者和密码重置设置。
*/
return [
   /**
    * 默认认证设置
    *
    * 指定默认的认证守卫和密码重置代理。
    */
   'defaults' => [
       'guard' => env('AUTH_GUARD', 'web'),           // 默认认证守卫
       'passwords' => env('AUTH_PASSWORD_BROKER', 'users'), // 默认密码重置代理
   ],

   /**
    * 认证守卫
    *
    * 定义应用程序可用的认证守卫。守卫决定如何验证用户身份。
    * 每个守卫需要一个驱动（如session或token）和一个提供者。
    */
   'guards' => [
       'web' => [
           'driver' => 'session',   // 基于会话的认证
           'provider' => 'users',   // 使用的用户提供者
       ],
   ],

   /**
    * 用户提供者
    *
    * 定义如何获取用户数据。通常使用Eloquent ORM或数据库查询构建器。
    */
   'providers' => [
       'users' => [
           'driver' => 'eloquent',  // 使用Eloquent ORM
           'model' => env('AUTH_MODEL', App\Models\User::class), // 用户模型类
       ],
   ],

   /**
    * 密码重置设置
    *
    * 控制密码重置令牌的存储和过期时间。
    */
   'passwords' => [
       'users' => [
           'provider' => 'users',   // 使用的用户提供者
           'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'), // 存储令牌的表
           'expire' => 60,          // 令牌有效期（分钟）
           'throttle' => 60,        // 限制密码重置请求的时间间隔（秒）
       ],
   ],

   /**
    * 密码确认超时
    *
    * 用户确认密码后的有效期（秒）。超过这个时间后需要重新确认。
    */
   'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800), // 默认3小时
];
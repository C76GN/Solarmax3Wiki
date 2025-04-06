<?php
App\Providers\BroadcastServiceProvider::class;
/**
* 应用程序配置
*
* 这个文件包含应用程序的基础配置设置，包括名称、环境、调试模式、
* 时区、本地化以及特定组件（如TinyMCE编辑器）的配置。
*/
return [

   /**
    * 应用名称
    *
    * 通过环境变量APP_NAME设置应用程序的名称，如未设置则默认为'Laravel'。
    */
   'name' => env('APP_NAME', 'Laravel'),

   /**
    * 应用环境
    *
    * 应用程序运行的环境，通常是'local'、'testing'或'production'。
    */
   'env' => env('APP_ENV', 'production'),

   /**
    * 调试模式
    *
    * 当开启调试模式时，应用会显示详细的错误信息。
    * 在生产环境中应设置为false以保护敏感信息。
    */
   'debug' => (bool) env('APP_DEBUG', false),

   /**
    * 应用URL
    *
    * 应用程序的基础URL，用于生成完整URL路径。
    */
   'url' => env('APP_URL', 'http://localhost'),

   /**
    * 应用时区
    *
    * 设置应用程序的默认时区。
    */
   'timezone' => env('APP_TIMEZONE', 'UTC'),

   /**
    * 本地化配置
    *
    * 设置应用程序的默认语言和备用语言。
    */
   'locale' => env('APP_LOCALE', 'en'),
   'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
   'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

   /**
    * 加密配置
    *
    * 用于加密会话等数据的密码和算法设置。
    */
   'cipher' => 'AES-256-CBC',
   'key' => env('APP_KEY'),
   'previous_keys' => [
       ...array_filter(
           explode(',', env('APP_PREVIOUS_KEYS', ''))
       ),
   ],

   /**
    * 维护模式配置
    *
    * 控制应用程序维护模式的驱动和存储方式。
    */
   'maintenance' => [
       'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
       'store' => env('APP_MAINTENANCE_STORE', 'database'),
   ],
];
<?php

return [


    'tinymce' => [
        'path' => '/tinymce',  // TinyMCE 资源文件路径
        'upload_path' => public_path('uploads/images'),  // 图片上传路径
        'upload_url' => '/uploads/images',  // 图片访问路径
    ],



    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application, which will be used when the
    | framework needs to place the application's name in a notification or
    | other UI elements where an application name needs to be displayed.
    |
    */
    /* 应用程序名称
    说明：定义应用程序的名称，通常在通知和用户界面中使用。
    来源：从 .env 文件获取，如果没有设置，则默认为 'Laravel'。
    */
    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */
    /* 应用环境
    说明：指示应用程序当前运行的环境（如开发、生产等），可用于配置不同的服务。
    来源：从 .env 文件获取，默认值为 'production'。
    */
    'env' => env('APP_ENV', 'production'),
    
    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */
    /* 调试模式
    说明：如果启用，应用程序将显示详细的错误信息和堆栈跟踪。否则，将显示简单的错误页面。
    来源：从 .env 文件获取，默认值为 false。
    */
    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | the application so that it's available within Artisan commands.
    |
    */
    /* 应用程序 URL
    说明：用于生成 Artisan 命令行工具中的 URL。应该设置为应用程序的根 URL。
    来源：从 .env 文件获取，默认值为 'http://localhost'。
    */
    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. The timezone
    | is set to "UTC" by default as it is suitable for most use cases.
    |
    */
    /* 应用程序时区
    说明：指定应用程序的默认时区，影响 PHP 日期和时间函数。
    来源：从 .env 文件获取，默认值为 'UTC'。
    */
    'timezone' => env('APP_TIMEZONE', 'UTC'),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by Laravel's translation / localization methods. This option can be
    | set to any locale for which you plan to have translation strings.
    |
    */
    /* 语言配置
    说明：设置应用程序的默认语言和备用语言，用于翻译和本地化方法。
    来源：从 .env 文件获取，默认值为 'en'（英语）。
    */
    'locale' => env('APP_LOCALE', 'en'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is utilized by Laravel's encryption services and should be set
    | to a random, 32 character string to ensure that all encrypted values
    | are secure. You should do this prior to deploying the application.
    |
    */
    /* 加密密钥
    说明：应用程序的加密密钥，确保所有加密值的安全性。应在应用程序部署前设置为随机的 32 个字符的字符串。
    来源：从 .env 文件获取。
    */
    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */
    /* 维护模式驱动
    说明：配置维护模式的状态，使用 file 或 cache 驱动程序来管理维护模式。
    来源：从 .env 文件获取，默认值为 'file'。
    */
    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

];

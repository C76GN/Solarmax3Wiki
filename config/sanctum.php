<?php

use Laravel\Sanctum\Sanctum;

/**
 * Sanctum 配置
 *
 * 此文件配置 Laravel Sanctum 包，用于提供 API 令牌认证功能，
 * 管理 SPA 认证以及发放 API 令牌。
 */
return [
    /**
     * 状态维持域名
     *
     * 这些域名将被视为"状态维持"，Sanctum会为这些域名发起的请求维护会话状态。
     * 这对于单页应用程序(SPA)与API认证的结合使用尤为重要。
     */
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
        '%s%s',
        'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
        Sanctum::currentApplicationUrlWithPort()
    ))),

    /**
     * 认证守卫
     *
     * Sanctum 使用的认证守卫，用于验证请求。
     */
    'guard' => ['web'],

    /**
     * 令牌过期时间
     *
     * 指定令牌过期的分钟数。如果设置为null，则令牌永不过期。
     */
    'expiration' => null,

    /**
     * 令牌前缀
     *
     * 添加到令牌哈希前的前缀，可用于区分不同应用的令牌。
     */
    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

    /**
     * Sanctum 中间件
     *
     * 这些中间件将应用于状态维持请求，以确保身份验证能够正常工作。
     */
    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
        'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ],
];

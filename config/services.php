<?php

/**
* 服务配置
*
* 此文件定义应用程序使用的各种第三方服务的配置信息，
* 包括邮件服务、云存储和通知服务等。
*/
return [
   /**
    * Postmark 邮件服务
    *
    * 用于通过 Postmark 发送电子邮件。
    */
   'postmark' => [
       'token' => env('POSTMARK_TOKEN'),
   ],

   /**
    * Amazon SES (Simple Email Service)
    *
    * 用于通过 AWS SES 发送电子邮件。
    */
   'ses' => [
       'key' => env('AWS_ACCESS_KEY_ID'),
       'secret' => env('AWS_SECRET_ACCESS_KEY'),
       'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
   ],

   /**
    * Resend 邮件服务
    *
    * 用于通过 Resend 发送电子邮件。
    */
   'resend' => [
       'key' => env('RESEND_KEY'),
   ],

   /**
    * Slack 通知服务
    *
    * 用于发送通知到 Slack 频道。
    */
   'slack' => [
       'notifications' => [
           'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
           'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
       ],
   ],
];
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 用户认证与会话管理数据表迁移
 *
 * 创建支持用户注册、登录、密码重置及会话管理所需的基础数据表。
 */
return new class extends Migration
{
    /**
     * 执行数据库迁移。
     *
     * 创建 `users`、`password_reset_tokens` 和 `sessions` 表。
     */
    public function up(): void
    {
        // 创建用户表
        Schema::create('users', function (Blueprint $table) {
            $table->id();                                   // 用户ID，自增主键
            $table->string('name');                         // 用户名
            $table->string('email')->unique();              // 邮箱，唯一
            $table->timestamp('email_verified_at')->nullable(); // 邮箱验证时间
            $table->string('password');                     // 密码哈希
            $table->rememberToken();                        // “记住我”令牌
            $table->timestamps();                           // 创建和更新时间戳
        });

        // 创建密码重置令牌表
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();             // 用户邮箱，主键
            $table->string('token');                        // 密码重置令牌
            $table->timestamp('created_at')->nullable();    // 令牌创建时间
        });

        // 创建会话表
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();                // 会话ID，主键
            $table->foreignId('user_id')->nullable()->index(); // 关联用户ID，可为空，带索引
            $table->string('ip_address', 45)->nullable();   // IP地址
            $table->text('user_agent')->nullable();         // 用户代理字符串
            $table->longText('payload');                    // 会话数据
            $table->integer('last_activity')->index();      // 最后活动时间，带索引
        });
    }

    /**
     * 回滚数据库迁移。
     *
     * 按照创建的逆序删除相关数据表。
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
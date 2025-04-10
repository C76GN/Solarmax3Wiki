<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 用户系统基础数据表迁移
 *
 * 创建用户认证和会话管理所需的基础表结构
 * 包括：用户表、密码重置令牌表、会话表
 */
return new class extends Migration
{
    /**
     * 执行迁移
     *
     * 创建用户认证系统所需的所有数据表
     */
    public function up(): void
    {
        // 创建用户表
        Schema::create('users', function (Blueprint $table) {
            $table->id();                                   // 自增主键
            $table->string('name');                         // 用户名
            $table->string('email')->unique();              // 电子邮箱（唯一）
            $table->timestamp('email_verified_at')->nullable(); // 邮箱验证时间
            $table->string('password');                     // 密码（已哈希）
            $table->rememberToken();                        // 记住我令牌
            $table->timestamps();                           // 创建和更新时间
        });

        // 创建密码重置令牌表
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();             // 电子邮箱（主键）
            $table->string('token');                        // 重置令牌
            $table->timestamp('created_at')->nullable();    // 创建时间
        });

        // 创建会话表
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();                // 会话ID（主键）
            $table->foreignId('user_id')->nullable()->index(); // 用户ID（外键）
            $table->string('ip_address', 45)->nullable();   // IP地址
            $table->text('user_agent')->nullable();         // 用户代理（浏览器信息）
            $table->longText('payload');                    // 会话数据
            $table->integer('last_activity')->index();      // 最后活动时间（索引）
        });
    }

    /**
     * 回滚迁移
     *
     * 按照依赖关系的反序删除所有创建的表
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};

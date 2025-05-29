<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 个人访问令牌表迁移
 *
 * 为Laravel Sanctum提供API认证功能，创建用于存储个人访问令牌的数据表。
 */
return new class extends Migration
{
    /**
     * 执行数据库迁移。
     *
     * 创建 `personal_access_tokens` 表。
     */
    public function up(): void
    {
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();                                   // 令牌ID，自增主键

            $table->morphs('tokenable');                    // 多态关联字段 (tokenable_type, tokenable_id)
                                                            // 用于关联到令牌所属的模型（通常是用户）

            $table->string('name');                         // 令牌名称
            $table->string('token', 64)->unique();          // 令牌哈希值，唯一
            $table->text('abilities')->nullable();          // 令牌权限列表，可为空
            $table->timestamp('last_used_at')->nullable();  // 令牌最后使用时间，可为空
            $table->timestamp('expires_at')->nullable();    // 令牌过期时间，可为空 (null表示永不过期)
            $table->timestamps();                           // 创建和更新时间戳
        });
    }

    /**
     * 回滚数据库迁移。
     *
     * 删除 `personal_access_tokens` 表。
     */
    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};
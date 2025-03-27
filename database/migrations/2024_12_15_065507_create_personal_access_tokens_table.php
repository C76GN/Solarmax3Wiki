<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 个人访问令牌表迁移
 * 
 * 创建用于存储API访问令牌的数据表
 * 支持Laravel Sanctum包的API认证功能
 */
return new class extends Migration
{
    /**
     * 执行迁移
     * 
     * 创建个人访问令牌表及其字段结构
     * 
     * @return void
     */
    public function up(): void
    {
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();                                   // 自增主键ID
            
            $table->morphs('tokenable');                    // 多态关联，包含tokenable_type和tokenable_id字段
                                                           // 用于关联到拥有此令牌的模型（通常是用户）
            
            $table->string('name');                         // 令牌名称（用于标识令牌用途）
            $table->string('token', 64)->unique();          // 哈希后的令牌值（唯一）
            $table->text('abilities')->nullable();          // 令牌的权限列表，可为空
            $table->timestamp('last_used_at')->nullable();  // 令牌最后使用时间，可为空
            $table->timestamp('expires_at')->nullable();    // 令牌过期时间，可为空（null表示永不过期）
            $table->timestamps();                           // 创建和更新时间戳
        });
    }

    /**
     * 回滚迁移
     * 
     * 删除个人访问令牌表
     * 
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 创建活动日志表的数据库迁移。
 *
 * 该表用于记录系统内的用户操作和重要事件，支持多态关联到不同模型。
 */
return new class extends Migration
{
    /**
     * 执行数据库迁移。
     *
     * 创建 `activity_logs` 表。
     */
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();                                       // 自增主键

            // 关联用户ID，当用户被删除时设为null
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('action');                           // 活动类型，如：创建、更新、删除

            // 多态关联字段，用于记录活动关联的模型实体
            $table->string('subject_type');                     // 关联模型类型
            $table->unsignedBigInteger('subject_id')->nullable(); // 关联模型ID

            $table->json('properties')->nullable();             // 活动相关额外数据，JSON格式
            $table->string('ip_address')->nullable();           // 操作者IP地址
            $table->string('user_agent')->nullable();           // 操作者用户代理信息
            $table->timestamps();                               // 记录创建和更新时间

            // 索引优化查询性能
            $table->index(['subject_type', 'subject_id']);      // 组合索引，按关联实体查询
            $table->index('action');                            // 索引，按活动类型查询
            $table->index('created_at');                        // 索引，按时间查询
        });
    }

    /**
     * 回滚数据库迁移。
     *
     * 删除 `activity_logs` 表。
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
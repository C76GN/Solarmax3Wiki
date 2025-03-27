<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 活动日志表迁移
 * 
 * 创建系统活动日志表，用于记录用户操作和系统事件
 * 支持多态关联，可以关联到系统中的任何模型实体
 */
return new class extends Migration
{
    /**
     * 执行迁移
     * 
     * 创建活动日志表及其相关字段和索引
     * 
     * @return void
     */
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();                                       // 自增主键ID
            
            // 关联用户ID，可为空，当用户被删除时设为null
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();                             // 允许记录非用户触发的系统活动
            
            $table->string('action');                           // 活动类型(如：create, update, delete, login)
            
            // 多态关联相关字段，记录活动关联的实体
            $table->string('subject_type');                     // 实体类型(如：App\Models\User, App\Models\Article)
            $table->unsignedBigInteger('subject_id')->nullable(); // 实体ID，可为null
            
            $table->json('properties')->nullable();             // 活动相关的额外数据，JSON格式储存
            $table->string('ip_address')->nullable();           // 操作者IP地址
            $table->string('user_agent')->nullable();           // 操作者浏览器/客户端信息
            $table->timestamps();                               // 创建和更新时间戳
            
            // 索引优化
            $table->index(['subject_type', 'subject_id']);      // 优化按实体类型和ID查询
            $table->index('action');                            // 优化按活动类型查询
            $table->index('created_at');                        // 优化按时间范围查询
        });
    }

    /**
     * 回滚迁移
     * 
     * 删除活动日志表
     * 
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
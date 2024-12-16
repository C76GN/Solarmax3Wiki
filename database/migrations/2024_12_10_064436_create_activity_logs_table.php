<?php
// FileName: /var/www/Solarmax3Wiki/database/migrations/2024_12_10_064436_create_activity_logs_table.php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');     // 操作类型，如：create, update, delete
            $table->string('subject_type'); // 操作对象类型，如：Template, Page
            $table->unsignedBigInteger('subject_id')->nullable(); // 操作对象ID
            $table->json('properties')->nullable(); // 操作详情，包括修改前后的值
            $table->string('ip_address')->nullable(); // 操作者IP
            $table->string('user_agent')->nullable(); // 用户代理
            $table->timestamps();
            
            // 为常用查询添加索引
            $table->index(['subject_type', 'subject_id']);
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
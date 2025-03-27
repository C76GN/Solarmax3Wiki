<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 缓存系统数据表迁移
 * 
 * 本迁移创建Laravel数据库缓存驱动所需的表结构
 * 包括：缓存数据表和缓存锁表
 */
return new class extends Migration
{
    /**
     * 执行迁移
     * 
     * 创建缓存系统所需的数据表
     * 
     * @return void
     */
    public function up(): void
    {
        // 创建缓存表
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();       // 缓存键（主键）
            $table->mediumText('value');            // 缓存值（中等长度文本）
            $table->integer('expiration');          // 过期时间戳
        });

        // 创建缓存锁表（用于分布式锁机制）
        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();       // 锁键名（主键）
            $table->string('owner');                // 锁的拥有者标识
            $table->integer('expiration');          // 锁的过期时间戳
        });
    }

    /**
     * 回滚迁移
     * 
     * 删除所有已创建的缓存相关表
     * 
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');
    }
};
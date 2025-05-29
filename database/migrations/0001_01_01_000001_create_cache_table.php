<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 缓存系统数据表迁移。
 *
 * 创建Laravel数据库缓存驱动和缓存锁机制所需的数据表结构。
 */
return new class extends Migration
{
    /**
     * 执行数据库迁移。
     *
     * 创建 `cache` 和 `cache_locks` 表。
     */
    public function up(): void
    {
        // 创建缓存表
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();       // 缓存键，主键
            $table->mediumText('value');            // 缓存值
            $table->integer('expiration');          // 过期时间戳
        });

        // 创建缓存锁表
        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();       // 锁的键名，主键
            $table->string('owner');                // 锁的持有者标识
            $table->integer('expiration');          // 锁的过期时间戳
        });
    }

    /**
     * 回滚数据库迁移。
     *
     * 删除 `cache` 和 `cache_locks` 表。
     */
    public function down(): void
    {
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');
    }
};
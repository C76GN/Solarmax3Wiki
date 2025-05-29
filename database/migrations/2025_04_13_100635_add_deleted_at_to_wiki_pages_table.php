<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 为 Wiki 页面表添加软删除功能。
 *
 * 允许 Wiki 页面被“软删除”，即标记为删除而不是从数据库中移除。
 */
return new class extends Migration
{
    /**
     * 执行数据库迁移。
     *
     * 在 `wiki_pages` 表中添加 `deleted_at` 字段。
     */
    public function up(): void
    {
        Schema::table('wiki_pages', function (Blueprint $table) {
            $table->softDeletes()->after('status'); // 添加软删除时间戳字段
        });
    }

    /**
     * 回滚数据库迁移。
     *
     * 从 `wiki_pages` 表中移除 `deleted_at` 字段。
     */
    public function down(): void
    {
        Schema::table('wiki_pages', function (Blueprint $table) {
            $table->dropSoftDeletes(); // 移除软删除功能
        });
    }
};
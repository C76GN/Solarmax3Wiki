<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wiki 页面版本管理数据表迁移。
 *
 * 创建 `wiki_versions` 表用于存储 Wiki 页面的历史版本，
 * 并为 `wiki_pages` 表添加 `current_version_id` 外键。
 */
return new class extends Migration
{
    /**
     * 执行数据库迁移。
     *
     * 创建 `wiki_versions` 表并添加 `wiki_pages` 表的外键。
     */
    public function up(): void
    {
        // 创建 Wiki 版本表
        Schema::create('wiki_versions', function (Blueprint $table) {
            $table->id();                                       // 版本ID，自增主键
            $table->foreignId('wiki_page_id')->constrained()->onDelete('cascade'); // 关联的Wiki页面ID，页面删除时版本级联删除
            $table->longText('content');                        // 版本内容
            $table->foreignId('created_by')->constrained('users'); // 创建此版本的用户ID
            $table->integer('version_number');                  // 版本号，按页面递增
            $table->string('comment')->nullable();              // 版本说明或提交评论
            $table->boolean('is_current')->default(false);      // 是否为当前活跃版本
            $table->json('diff_from_previous')->nullable();     // 与上一版本差异的JSON数据
            $table->timestamps();                               // 创建和更新时间戳

            $table->index('wiki_page_id');                      // 为页面ID添加索引
            $table->index('version_number');                    // 为版本号添加索引
            $table->index('is_current');                        // 为是否当前版本添加索引
            $table->unique(['wiki_page_id', 'version_number']); // 确保同一页面下版本号唯一
        });

        // 为 `wiki_pages` 表添加 `current_version_id` 外键
        Schema::table('wiki_pages', function (Blueprint $table) {
            $table->foreign('current_version_id')               // 当前版本ID
                ->references('id')                              // 引用 `wiki_versions` 表的 `id`
                ->on('wiki_versions')                           // 引用 `wiki_versions` 表
                ->nullOnDelete();                               // 如果关联版本被删除，则设为NULL
        });
    }

    /**
     * 回滚数据库迁移。
     *
     * 移除 `wiki_pages` 表的外键，然后删除 `wiki_versions` 表。
     */
    public function down(): void
    {
        // 移除 `wiki_pages` 表的 `current_version_id` 外键
        Schema::table('wiki_pages', function (Blueprint $table) {
            $table->dropForeign(['current_version_id']);
        });

        // 删除 `wiki_versions` 表
        Schema::dropIfExists('wiki_versions');
    }
};
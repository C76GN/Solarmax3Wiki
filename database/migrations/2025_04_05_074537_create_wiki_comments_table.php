<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wiki 评论数据表迁移
 *
 * 创建 `wiki_comments` 表，用于存储 Wiki 页面的评论和回复。
 * 支持多级评论（通过 `parent_id` 自引用）。
 */
return new class extends Migration
{
    /**
     * 执行数据库迁移。
     *
     * 创建 `wiki_comments` 表及其索引和外键约束。
     */
    public function up(): void
    {
        Schema::create('wiki_comments', function (Blueprint $table) {
            $table->id();                                   // 评论ID，自增主键
            $table->foreignId('wiki_page_id')               // 关联的Wiki页面ID
                  ->constrained()                           // 约束到 `wiki_pages` 表的 `id` 字段
                  ->onDelete('cascade');                    // 当关联页面删除时，评论也随之删除

            $table->foreignId('user_id')                    // 发布评论的用户ID
                  ->constrained()                           // 约束到 `users` 表的 `id` 字段
                  ->onDelete('cascade');                    // 当关联用户删除时，评论也随之删除

            $table->foreignId('parent_id')->nullable();     // 父评论ID，用于回复，可为空

            $table->text('content');                        // 评论内容
            $table->boolean('is_hidden')->default(false);   // 评论是否隐藏（例如被管理员隐藏）
            $table->timestamps();                           // 创建和更新时间戳

            $table->index('wiki_page_id');                  // 为 `wiki_page_id` 添加索引以优化查询
            $table->index('user_id');                       // 为 `user_id` 添加索引以优化查询
            $table->index('parent_id');                     // 为 `parent_id` 添加索引以优化查询
        });

        // 为 `wiki_comments` 表的 `parent_id` 字段添加自引用外键约束
        Schema::table('wiki_comments', function (Blueprint $table) {
            $table->foreign('parent_id')                    // 定义 `parent_id` 为外键
                  ->references('id')                        // 引用 `wiki_comments` 表的 `id` 字段
                  ->on('wiki_comments')                     // 引用自身表
                  ->nullOnDelete();                         // 当父评论删除时，子评论的 `parent_id` 设为 null
        });
    }

    /**
     * 回滚数据库迁移。
     *
     * 删除 `wiki_comments` 表。
     */
    public function down(): void
    {
        Schema::dropIfExists('wiki_comments');
    }
};
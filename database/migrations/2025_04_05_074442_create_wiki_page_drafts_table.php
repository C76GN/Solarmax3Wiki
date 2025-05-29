<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wiki页面草稿数据表迁移
 *
 * 创建和管理Wiki页面编辑草稿的数据库表。
 */
return new class extends Migration
{
    /**
     * 执行数据库迁移。
     *
     * 创建 `wiki_page_drafts` 表。
     */
    public function up(): void
    {
        Schema::create('wiki_page_drafts', function (Blueprint $table) {
            $table->id();                                   // 自增主键
            $table->foreignId('wiki_page_id')->constrained()->onDelete('cascade'); // 关联的Wiki页面ID，外键，级联删除
            $table->foreignId('user_id')->constrained()->onDelete('cascade');     // 草稿创建者用户ID，外键，级联删除
            $table->longText('content');                    // 草稿内容
            $table->timestamp('last_saved_at');             // 最后保存时间
            $table->timestamps();                           // 创建和更新时间戳

            $table->index(['wiki_page_id', 'user_id']);     // 为页面ID和用户ID创建联合索引
            $table->unique(['wiki_page_id', 'user_id']);    // 确保每个用户对每个页面只有一个草稿
        });
    }

    /**
     * 回滚数据库迁移。
     *
     * 删除 `wiki_page_drafts` 表。
     */
    public function down(): void
    {
        Schema::dropIfExists('wiki_page_drafts');
    }
};
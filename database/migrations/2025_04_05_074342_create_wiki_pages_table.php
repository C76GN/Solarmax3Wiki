<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wiki 页面数据表迁移
 *
 * 创建 Wiki 页面的主表结构，包括标题、Slug、状态、创建者、当前版本、锁定状态等。
 */
return new class extends Migration
{
    /**
     * 执行数据库迁移。
     *
     * 创建 `wiki_pages` 表。
     */
    public function up(): void
    {
        Schema::create('wiki_pages', function (Blueprint $table) {
            $table->id();                                   // 页面ID，自增主键
            $table->string('title');                        // 页面标题
            $table->string('slug')->unique();               // 页面Slug，用于友好URL，唯一
            $table->enum('status', ['draft', 'published', 'conflict'])->default('draft'); // 页面状态：草稿、已发布、冲突
            $table->foreignId('created_by')->constrained('users'); // 创建者用户ID，外键关联 `users` 表
            $table->foreignId('current_version_id')->nullable(); // 当前版本ID，外键将在 `wiki_versions` 表创建后添加
            $table->boolean('is_locked')->default(false);   // 页面是否被锁定编辑
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete(); // 锁定者用户ID，可为空，外键关联 `users` 表，删除用户时设为NULL
            $table->timestamp('locked_until')->nullable();  // 锁定截止时间
            $table->timestamps();                           // 创建和更新时间戳

            $table->index('slug');                          // 为 Slug 字段添加索引，加快查询速度
            $table->index('status');                        // 为 Status 字段添加索引，加快查询速度
        });
    }

    /**
     * 回滚数据库迁移。
     *
     * 删除 `wiki_pages` 表。
     */
    public function down(): void
    {
        Schema::dropIfExists('wiki_pages');
    }
};
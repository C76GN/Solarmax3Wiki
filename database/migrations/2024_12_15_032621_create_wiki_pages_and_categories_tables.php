<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 维基系统数据表迁移
 * 
 * 创建维基系统核心表结构，包括：
 * - 维基页面表
 * - 维基分类表
 * - 维基页面与分类的多对多关联表
 */
return new class extends Migration
{
    /**
     * 执行迁移
     * 
     * 创建维基系统所需的所有数据表及其关联关系
     * 
     * @return void
     */
    public function up(): void
    {
        // 创建维基页面表 - 存储所有维基文章内容
        Schema::create('wiki_pages', function (Blueprint $table) {
            $table->id();                                           // 自增主键ID
            $table->string('title')->unique();                      // 页面标题（唯一）
            $table->string('slug')->unique();                       // URL友好的标识符（唯一）
            $table->text('content')->nullable();                    // 页面内容，可为空
            $table->string('status')->default('draft');             // 页面状态（草稿、已发布等）
            
            // 创建者外键 - 关联到users表
            $table->foreignId('created_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();                                 // 创建者被删除时设为null
            
            // 最后编辑者外键 - 关联到users表
            $table->foreignId('last_edited_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();                                 // 编辑者被删除时设为null
            
            $table->timestamp('published_at')->nullable();          // 发布时间
            $table->integer('view_count')->default(0);              // 浏览次数
            $table->timestamps();                                   // 创建和更新时间戳
            $table->softDeletes();                                  // 软删除支持（回收站功能）
            $table->fullText(['title', 'content']);                 // 全文索引支持搜索功能
        });

        // 创建维基分类表 - 用于组织维基页面
        Schema::create('wiki_categories', function (Blueprint $table) {
            $table->id();                                           // 自增主键ID
            $table->string('name');                                 // 分类名称
            $table->string('slug')->unique();                       // URL友好的标识符（唯一）
            $table->text('description')->nullable();                // 分类描述
            $table->unsignedBigInteger('parent_id')->nullable();    // 父分类ID，支持层级结构
            $table->integer('order')->default(0);                   // 排序顺序
            $table->timestamps();                                   // 创建和更新时间戳
            $table->softDeletes();                                  // 软删除支持
            
            // 自引用外键 - 实现分类的层级结构
            $table->foreign('parent_id')
                  ->references('id')
                  ->on('wiki_categories')
                  ->onDelete('cascade');                            // 父分类删除时，子分类也删除
        });

        // 创建维基页面与分类的多对多关联表
        Schema::create('wiki_page_category', function (Blueprint $table) {
            $table->id();                                           // 自增主键ID
            
            // 维基页面外键
            $table->foreignId('wiki_page_id')
                  ->constrained()
                  ->onDelete('cascade');                            // 页面删除时，关联也删除
            
            // 维基分类外键
            $table->foreignId('wiki_category_id')
                  ->constrained()
                  ->onDelete('cascade');                            // 分类删除时，关联也删除
            
            $table->timestamps();                                   // 创建和更新时间戳
            
            // 确保每个页面在每个分类中只出现一次
            $table->unique(['wiki_page_id', 'wiki_category_id']);   // 组合唯一约束
        });
    }

    /**
     * 回滚迁移
     * 
     * 按照依赖关系的反序删除所有创建的表
     * 
     * @return void
     */
    public function down(): void
    {
        // 必须先删除关联表，再删除主表，以避免外键约束错误
        Schema::dropIfExists('wiki_page_category');
        Schema::dropIfExists('wiki_categories');
        Schema::dropIfExists('wiki_pages');
    }
};
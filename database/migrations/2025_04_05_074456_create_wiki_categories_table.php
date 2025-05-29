<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wiki 分类及页面分类关联表迁移
 *
 * 创建 Wiki 分类结构及其与 Wiki 页面之间的多对多关联。
 */
return new class extends Migration
{
    /**
     * 执行数据库迁移。
     *
     * 创建 `wiki_categories` 表（包括自引用父子关系）和 `wiki_page_category` 关联表。
     */
    public function up(): void
    {
        // 创建 Wiki 分类表
        Schema::create('wiki_categories', function (Blueprint $table) {
            $table->id();                                   // 分类ID，自增主键
            $table->string('name');                         // 分类名称
            $table->string('slug')->unique();               // 分类Slug，唯一，用于URL
            $table->text('description')->nullable();        // 分类描述，可为空
            $table->foreignId('parent_id')->nullable();     // 父分类ID，用于层级结构，可为空
            $table->integer('order')->default(0);           // 排序顺序，默认0
            $table->timestamps();                           // 创建和更新时间戳

            $table->index('slug');                          // 为 slug 字段添加索引
            $table->index('parent_id');                     // 为 parent_id 字段添加索引
        });

        // 为 Wiki 分类表添加自引用外键约束
        Schema::table('wiki_categories', function (Blueprint $table) {
            $table->foreign('parent_id')
                ->references('id')
                ->on('wiki_categories')
                ->nullOnDelete();                           // 父分类被删除时，子分类的 parent_id 设为 NULL
        });

        // 创建 Wiki 页面与分类的关联表（中间表）
        Schema::create('wiki_page_category', function (Blueprint $table) {
            $table->id();                                   // 关联ID，自增主键
            $table->foreignId('wiki_page_id')->constrained()->onDelete('cascade'); // 关联 Wiki 页面ID，页面删除时级联删除
            $table->foreignId('wiki_category_id')->constrained()->onDelete('cascade'); // 关联 Wiki 分类ID，分类删除时级联删除
            $table->timestamps();                           // 创建和更新时间戳

            $table->unique(['wiki_page_id', 'wiki_category_id']); // 确保页面和分类的组合是唯一的
        });
    }

    /**
     * 回滚数据库迁移。
     *
     * 按照创建的逆序删除相关数据表。
     */
    public function down(): void
    {
        Schema::dropIfExists('wiki_page_category'); // 先删除关联表
        Schema::dropIfExists('wiki_categories');    // 再删除分类表
    }
};
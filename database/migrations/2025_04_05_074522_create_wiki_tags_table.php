<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wiki 标签及其关联表的迁移
 *
 * 创建 Wiki 标签表和 Wiki 页面与标签的关联表。
 */
return new class extends Migration
{
    /**
     * 执行数据库迁移。
     *
     * 创建 `wiki_tags` 表和 `wiki_page_tag` 关联表。
     */
    public function up(): void
    {
        // 创建 Wiki 标签表
        Schema::create('wiki_tags', function (Blueprint $table) {
            $table->id();                                   // 标签ID，自增主键
            $table->string('name');                         // 标签名称
            $table->string('slug')->unique();              // 标签slug，唯一
            $table->timestamps();                           // 创建和更新时间戳

            $table->index('slug');                          // 为 slug 字段添加索引
        });

        // 创建 Wiki 页面与标签的关联表 (多对多关系)
        Schema::create('wiki_page_tag', function (Blueprint $table) {
            $table->id();                                   // 关联ID，自增主键
            $table->foreignId('wiki_page_id')               // Wiki页面ID
                  ->constrained()                           // 关联到 `wiki_pages` 表
                  ->onDelete('cascade');                    // 父记录删除时，级联删除
            $table->foreignId('wiki_tag_id')                // Wiki标签ID
                  ->constrained()                           // 关联到 `wiki_tags` 表
                  ->onDelete('cascade');                    // 父记录删除时，级联删除
            $table->timestamps();                           // 创建和更新时间戳

            // 为 `wiki_page_id` 和 `wiki_tag_id` 创建联合唯一索引，确保每对关联的唯一性
            $table->unique(['wiki_page_id', 'wiki_tag_id']);
        });
    }

    /**
     * 回滚数据库迁移。
     *
     * 按照依赖关系的反序删除相关数据表。
     */
    public function down(): void
    {
        Schema::dropIfExists('wiki_page_tag'); // 先删除关联表
        Schema::dropIfExists('wiki_tags');     // 再删除标签表
    }
};
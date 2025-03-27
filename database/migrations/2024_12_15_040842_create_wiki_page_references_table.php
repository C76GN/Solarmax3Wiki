<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 维基页面引用关系表迁移
 * 
 * 创建维基页面之间的引用关系表
 * 用于跟踪页面间的内部链接和引用关系，支持页面导航和相关内容推荐
 */
return new class extends Migration
{
    /**
     * 执行迁移
     * 
     * 创建维基页面引用关系表及其约束
     * 
     * @return void
     */
    public function up(): void
    {
        Schema::create('wiki_page_references', function (Blueprint $table) {
            $table->id();                                           // 自增主键ID
            
            // 源页面外键 - 引用其他页面的页面
            $table->foreignId('source_page_id')
                  ->constrained('wiki_pages')
                  ->onDelete('cascade');                            // 源页面删除时，相关的引用记录也会被删除
            
            // 目标页面外键 - 被引用的页面
            $table->foreignId('target_page_id')
                  ->constrained('wiki_pages')
                  ->onDelete('cascade');                            // 目标页面删除时，相关的引用记录也会被删除
            
            $table->text('context')->nullable();                    // 引用的上下文内容，可为空
            $table->timestamps();                                   // 创建和更新时间戳
            
            // 确保同一个源页面不会多次引用同一个目标页面
            $table->unique(['source_page_id', 'target_page_id']);   // 源页面和目标页面的组合必须唯一
        });
    }

    /**
     * 回滚迁移
     * 
     * 删除维基页面引用关系表
     * 
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('wiki_page_references');
    }
};
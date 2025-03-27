<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 维基页面修订历史表迁移
 * 
 * 创建维基系统的版本控制功能所需的修订历史表，
 * 并为wiki_pages表添加跟踪当前版本的字段
 */
return new class extends Migration
{
    /**
     * 执行迁移
     * 
     * 创建维基页面修订历史表并更新维基页面表结构
     * 
     * @return void
     */
    public function up(): void
    {
        // 创建维基页面修订历史表 - 存储页面的所有历史版本
        Schema::create('wiki_page_revisions', function (Blueprint $table) {
            $table->id();                                           // 自增主键ID
            
            // 关联到维基页面的外键
            $table->foreignId('wiki_page_id')
                  ->constrained()
                  ->onDelete('cascade');                            // 页面删除时，其所有修订历史也会被删除
            
            $table->text('content');                                // 该版本的页面内容
            $table->string('title');                                // 该版本的页面标题
            $table->text('comment')->nullable();                    // 修订说明/注释，可为空
            
            // 修订创建者的外键
            $table->foreignId('created_by')
                  ->constrained('users')
                  ->onDelete('cascade');                            // 用户删除时，其创建的修订记录也会被删除
            
            $table->json('changes')->nullable();                    // 与前一版本的差异信息，JSON格式存储
            $table->integer('version');                             // 版本号（每个页面从1开始递增）
            $table->timestamps();                                   // 创建和更新时间戳
            
            // 确保每个页面的每个版本号都是唯一的
            $table->unique(['wiki_page_id', 'version']);            // 组合唯一约束
        });

        // 更新维基页面表，添加当前版本字段
        Schema::table('wiki_pages', function (Blueprint $table) {
            $table->integer('current_version')->default(0);         // 当前版本号，默认为0（未创建修订时）
        });
    }

    /**
     * 回滚迁移
     * 
     * 先删除添加的字段，再删除整个修订历史表
     * 
     * @return void
     */
    public function down(): void
    {
        // 先移除wiki_pages表中添加的字段
        Schema::table('wiki_pages', function (Blueprint $table) {
            $table->dropColumn('current_version');                  // 删除当前版本字段
        });
        
        // 再删除整个修订历史表
        Schema::dropIfExists('wiki_page_revisions');
    }
};
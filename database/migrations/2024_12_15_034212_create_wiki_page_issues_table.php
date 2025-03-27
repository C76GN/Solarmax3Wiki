<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 维基页面问题报告表迁移
 * 
 * 创建维基页面问题报告系统所需的数据表
 * 用于跟踪和管理用户对维基页面报告的问题或改进建议
 */
return new class extends Migration
{
    /**
     * 执行迁移
     * 
     * 创建维基页面问题报告表及其关联关系
     * 
     * @return void
     */
    public function up(): void
    {
        Schema::create('wiki_page_issues', function (Blueprint $table) {
            $table->id();                                           // 自增主键ID
            
            // 维基页面外键 - 关联到wiki_pages表
            $table->foreignId('wiki_page_id')
                  ->constrained()
                  ->onDelete('cascade');                            // 页面被删除时，相关的问题报告也会被删除
            
            // 报告者外键 - 关联到users表
            $table->foreignId('reported_by')
                  ->constrained('users')
                  ->onDelete('cascade');                            // 报告者被删除时，其报告的问题也会被删除
            
            $table->text('content');                                // 问题报告的详细内容
            $table->string('status')->default('open');              // 问题状态（如：open, in_progress, resolved, closed）
            
            // 解决者外键 - 关联到users表，可为空
            $table->foreignId('resolved_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();                                 // 解决者被删除时，设为null而不删除问题报告
            
            $table->timestamp('resolved_at')->nullable();           // 问题解决的时间，可为空
            $table->timestamps();                                   // 创建和更新时间戳
        });
    }

    /**
     * 回滚迁移
     * 
     * 删除维基页面问题报告表
     * 
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('wiki_page_issues');
    }
};
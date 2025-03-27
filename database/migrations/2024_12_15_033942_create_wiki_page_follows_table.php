<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 维基页面关注表迁移
 * 
 * 创建用户关注维基页面的关联表
 * 实现用户可以关注感兴趣的维基页面的功能
 */
return new class extends Migration
{
    /**
     * 执行迁移
     * 
     * 创建维基页面关注表及其关联约束
     * 
     * @return void
     */
    public function up(): void
    {
        Schema::create('wiki_page_follows', function (Blueprint $table) {
            $table->id();                                           // 自增主键ID
            
            // 用户外键 - 关联到users表
            $table->foreignId('user_id')
                  ->constrained()
                  ->onDelete('cascade');                            // 用户被删除时，其所有关注记录也会被删除
            
            // 维基页面外键 - 关联到wiki_pages表
            $table->foreignId('wiki_page_id')
                  ->constrained()
                  ->onDelete('cascade');                            // 页面被删除时，相关的关注记录也会被删除
            
            $table->timestamps();                                   // 创建和更新时间戳
            
            // 确保用户不能重复关注同一个页面
            $table->unique(['user_id', 'wiki_page_id']);            // 用户ID和页面ID的组合必须唯一
        });
    }

    /**
     * 回滚迁移
     * 
     * 删除维基页面关注表
     * 
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('wiki_page_follows');
    }
};
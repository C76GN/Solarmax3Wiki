<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wiki_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->foreignId('parent_id')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
            
            $table->index('slug');
            $table->index('parent_id');
        });
        
        // 添加自引用关系
        Schema::table('wiki_categories', function (Blueprint $table) {
            $table->foreign('parent_id')
                  ->references('id')
                  ->on('wiki_categories')
                  ->nullOnDelete();
        });
        
        // 创建页面分类关联表
        Schema::create('wiki_page_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wiki_page_id')->constrained()->onDelete('cascade');
            $table->foreignId('wiki_category_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['wiki_page_id', 'wiki_category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wiki_page_category');
        Schema::dropIfExists('wiki_categories');
    }
};
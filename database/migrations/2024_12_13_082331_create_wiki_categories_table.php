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
            $table->string('name');                  // 分类名称
            $table->string('slug')->unique();        // URL友好的标识
            $table->text('description')->nullable(); // 分类描述
            $table->unsignedBigInteger('parent_id')->nullable(); // 父分类ID
            $table->integer('order')->default(0);    // 排序
            $table->timestamps();
            $table->softDeletes();

            // 外键约束
            $table->foreign('parent_id')
                  ->references('id')
                  ->on('wiki_categories')
                  ->onDelete('cascade');
        });

        // 添加分类关联表
        Schema::create('wiki_article_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wiki_article_id')->constrained()->onDelete('cascade');
            $table->foreignId('wiki_category_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            // 确保文章在同一分类下只能出现一次
            $table->unique(['wiki_article_id', 'wiki_category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wiki_article_category');
        Schema::dropIfExists('wiki_categories');
    }
};
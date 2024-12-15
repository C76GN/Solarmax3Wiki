<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 创建 wiki_pages 表
        Schema::create('wiki_pages', function (Blueprint $table) {
            $table->id();
            $table->string('title')->unique();
            $table->string('slug')->unique();
            $table->text('content')->nullable();
            $table->string('status')->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('last_edited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->integer('view_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
            
            $table->fullText(['title', 'content']);
        });

        // 创建 wiki_categories 表
        Schema::create('wiki_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('parent_id')
                  ->references('id')
                  ->on('wiki_categories')
                  ->onDelete('cascade');
        });

        // 创建关联表
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
        Schema::dropIfExists('wiki_pages');
    }
};
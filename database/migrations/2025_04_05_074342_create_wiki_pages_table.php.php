<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wiki_pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->enum('status', ['draft', 'published', 'conflict'])->default('draft');
            $table->foreignId('template_id')->nullable()->constrained('wiki_templates')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('current_version_id')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('locked_until')->nullable();
            $table->foreignId('parent_id')->nullable();
            $table->integer('order')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
            
            $table->index('slug');
            $table->index('status');
            $table->index('parent_id');
        });

        // 添加自引用关系
        Schema::table('wiki_pages', function (Blueprint $table) {
            $table->foreign('parent_id')
                  ->references('id')
                  ->on('wiki_pages')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wiki_pages');
    }
};
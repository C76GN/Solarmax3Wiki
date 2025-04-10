<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wiki_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();

            $table->index('slug');
        });

        // 创建页面标签关联表
        Schema::create('wiki_page_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wiki_page_id')->constrained()->onDelete('cascade');
            $table->foreignId('wiki_tag_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['wiki_page_id', 'wiki_tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wiki_page_tag');
        Schema::dropIfExists('wiki_tags');
    }
};

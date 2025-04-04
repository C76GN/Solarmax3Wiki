<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wiki_page_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wiki_page_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('content');
            $table->json('categories')->nullable();
            $table->timestamps();
            
            // 每个用户对每个页面只能有一个草稿
            $table->unique(['user_id', 'wiki_page_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wiki_page_drafts');
    }
};
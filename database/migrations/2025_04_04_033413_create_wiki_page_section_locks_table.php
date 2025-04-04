<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wiki_page_section_locks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wiki_page_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('section_id');  // 段落或区域ID，如h2-introduction
            $table->text('section_title')->nullable(); // 区域标题，如"介绍"
            $table->timestamp('expires_at');  // 锁定过期时间
            $table->timestamps();
            
            // 确保同一页面同一区域不会被多人锁定
            $table->unique(['wiki_page_id', 'section_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wiki_page_section_locks');
    }
};
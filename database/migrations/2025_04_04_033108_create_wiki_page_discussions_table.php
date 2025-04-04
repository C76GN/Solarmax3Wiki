<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wiki_page_discussions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wiki_page_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('message');
            $table->string('editing_section')->nullable(); // 正在编辑的章节或区域
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wiki_page_discussions');
    }
};
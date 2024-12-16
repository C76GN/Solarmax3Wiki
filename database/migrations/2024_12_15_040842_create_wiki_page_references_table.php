<?php
// FileName: /var/www/Solarmax3Wiki/database/migrations/2024_12_15_040842_create_wiki_page_references_table.php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wiki_page_references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_page_id')->constrained('wiki_pages')->onDelete('cascade');
            $table->foreignId('target_page_id')->constrained('wiki_pages')->onDelete('cascade');
            $table->text('context')->nullable(); // 引用上下文
            $table->timestamps();

            // 确保同一个引用关系只记录一次
            $table->unique(['source_page_id', 'target_page_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wiki_page_references');
    }
};
<?php
// FileName: /var/www/Solarmax3Wiki/database/migrations/2024_12_15_033942_create_wiki_page_follows_table.php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wiki_page_follows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('wiki_page_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['user_id', 'wiki_page_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wiki_page_follows');
    }
};
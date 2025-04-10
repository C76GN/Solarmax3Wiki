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
            $table->longText('content');
            $table->timestamp('last_saved_at');
            $table->timestamps();

            $table->index(['wiki_page_id', 'user_id']);
            $table->unique(['wiki_page_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wiki_page_drafts');
    }
};

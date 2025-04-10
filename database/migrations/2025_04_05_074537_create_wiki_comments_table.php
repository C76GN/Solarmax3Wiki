<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wiki_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wiki_page_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('parent_id')->nullable();
            $table->text('content');
            $table->boolean('is_hidden')->default(false);
            $table->timestamps();
            
            $table->index('wiki_page_id');
            $table->index('user_id');
            $table->index('parent_id');
        });
        
        // 添加自引用关系
        Schema::table('wiki_comments', function (Blueprint $table) {
            $table->foreign('parent_id')
                  ->references('id')
                  ->on('wiki_comments')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wiki_comments');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wiki_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wiki_page_id')->constrained()->onDelete('cascade');
            $table->longText('content');
            $table->foreignId('created_by')->constrained('users');
            $table->integer('version_number');
            $table->string('comment')->nullable();
            $table->boolean('is_current')->default(false);
            $table->json('diff_from_previous')->nullable();
            $table->timestamps();
            
            $table->index('wiki_page_id');
            $table->index('version_number');
            $table->index('is_current');
            $table->unique(['wiki_page_id', 'version_number']);
        });
        
        // 添加current_version_id外键
        Schema::table('wiki_pages', function (Blueprint $table) {
            $table->foreign('current_version_id')
                  ->references('id')
                  ->on('wiki_versions')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('wiki_pages', function (Blueprint $table) {
            $table->dropForeign(['current_version_id']);
        });
        
        Schema::dropIfExists('wiki_versions');
    }
};
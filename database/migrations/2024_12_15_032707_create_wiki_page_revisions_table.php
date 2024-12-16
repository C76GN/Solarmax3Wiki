<?php
// FileName: /var/www/Solarmax3Wiki/database/migrations/2024_12_15_032707_create_wiki_page_revisions_table.php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wiki_page_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wiki_page_id')->constrained()->onDelete('cascade');
            $table->text('content');
            $table->string('title');
            $table->text('comment')->nullable(); // 修改说明
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->json('changes')->nullable(); // 存储差异信息
            $table->integer('version');
            $table->timestamps();

            $table->unique(['wiki_page_id', 'version']); // 确保版本号唯一
        });

        // 在wiki_pages表中添加当前版本号字段
        Schema::table('wiki_pages', function (Blueprint $table) {
            $table->integer('current_version')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('wiki_pages', function (Blueprint $table) {
            $table->dropColumn('current_version');
        });
        Schema::dropIfExists('wiki_page_revisions');
    }
};
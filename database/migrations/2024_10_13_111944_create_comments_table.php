<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // 关联用户
            $table->foreignId('game_version_id')->nullable()->constrained('game_versions')->onDelete('cascade'); // 关联游戏版本
            $table->foreignId('chapter_id')->nullable()->constrained('game_chapters')->onDelete('cascade'); // 关联章节
            $table->foreignId('level_id')->nullable()->constrained('levels')->onDelete('cascade'); // 关联关卡
            $table->text('content'); // 评论内容
            $table->integer('rating'); // 评分
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};

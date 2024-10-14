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
        Schema::create('game_chapters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_version_id')->constrained('game_versions')->onDelete('cascade'); // 关联游戏版本
            $table->string('name'); // 章节名称
            $table->text('description'); // 章节描述
            $table->integer('required_stars'); // 需要的星星数量
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_chapters');
    }
};

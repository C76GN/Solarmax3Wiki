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
        Schema::table('wiki_pages', function (Blueprint $table) {
            // 在 status 字段之后添加 deleted_at 字段
            $table->softDeletes()->after('status'); // 使用 softDeletes 方法
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wiki_pages', function (Blueprint $table) {
            $table->dropSoftDeletes(); // 移除软删除字段
        });
    }
};

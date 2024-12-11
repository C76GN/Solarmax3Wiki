<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            // 添加搜索相关字段
            $table->string('status')->default('draft')->after('is_published'); // 状态：draft, published, archived
            $table->timestamp('last_edited_at')->nullable()->after('published_at');
            $table->foreignId('last_edited_by')->nullable()->after('last_edited_at')->constrained('users')->nullOnDelete();
            $table->integer('view_count')->default(0)->after('last_edited_by');
            
            // 添加全文搜索索引
            $table->fullText(['title', 'slug']);
            
            // 修改 published_at 字段的默认值
            $table->timestamp('published_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            // 删除全文搜索索引
            $table->dropFullText(['title', 'slug']);
            
            // 删除添加的字段
            $table->dropForeign(['last_edited_by']);
            $table->dropColumn([
                'status',
                'last_edited_at',
                'last_edited_by',
                'view_count'
            ]);
        });
    }
};
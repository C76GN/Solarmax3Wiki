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
        Schema::create('wiki_pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->enum('status', ['draft', 'published', 'conflict'])->default('draft');
            $table->foreignId('created_by')->constrained('users');
            // current_version_id 将在下一个迁移文件中添加外键
            $table->foreignId('current_version_id')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('locked_until')->nullable();
            // 移除 parent_id 和 order
            // $table->foreignId('parent_id')->nullable();
            // $table->integer('order')->default(0);
            $table->timestamps();

            $table->index('slug');
            $table->index('status');
            // 移除 parent_id 索引
            // $table->index('parent_id');
        });

        // 移除 parent_id 外键约束
        // Schema::table('wiki_pages', function (Blueprint $table) {
        //     $table->foreign('parent_id')
        //           ->references('id')
        //           ->on('wiki_pages')
        //           ->nullOnDelete();
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 注意：移除外键的操作通常放在 up 方法对应的 table 定义内部或单独的 Schema::table 中
        // 但既然我们要完全移除，这里也注释掉 down 方法中的相关逻辑，如果需要回滚，需要手动调整
        // Schema::table('wiki_pages', function (Blueprint $table) {
        //     $table->dropForeign(['parent_id']);
        // });
        Schema::dropIfExists('wiki_pages');
    }
};
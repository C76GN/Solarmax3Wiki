<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 队列系统相关数据表迁移
 *
 * 创建Laravel队列系统所需的表结构，包括：
 * - 队列任务表
 * - 批量任务表
 * - 失败任务表
 */
return new class extends Migration
{
    /**
     * 执行迁移
     *
     * 创建队列系统所需的所有数据表
     */
    public function up(): void
    {
        // 创建队列任务表 - 存储待处理的队列任务
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();                                   // 自增主键
            $table->string('queue')->index();               // 队列名称（已索引）
            $table->longText('payload');                    // 任务数据负载
            $table->unsignedTinyInteger('attempts');        // 尝试执行次数
            $table->unsignedInteger('reserved_at')->nullable(); // 任务被保留的时间戳
            $table->unsignedInteger('available_at');        // 任务可执行的时间戳
            $table->unsignedInteger('created_at');          // 任务创建的时间戳
        });

        // 创建批量任务表 - 管理批量任务的执行
        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();                // 批次ID（主键）
            $table->string('name');                         // 批次名称
            $table->integer('total_jobs');                  // 总任务数
            $table->integer('pending_jobs');                // 待处理任务数
            $table->integer('failed_jobs');                 // 失败任务数
            $table->longText('failed_job_ids');             // 失败任务ID列表
            $table->mediumText('options')->nullable();      // 批次选项
            $table->integer('cancelled_at')->nullable();    // 取消时间戳
            $table->integer('created_at');                  // 创建时间戳
            $table->integer('finished_at')->nullable();     // 完成时间戳
        });

        // 创建失败任务表 - 记录执行失败的任务
        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();                                   // 自增主键
            $table->string('uuid')->unique();               // 唯一标识符
            $table->text('connection');                     // 队列连接信息
            $table->text('queue');                          // 队列名称
            $table->longText('payload');                    // 任务数据负载
            $table->longText('exception');                  // 异常信息
            $table->timestamp('failed_at')->useCurrent();   // 失败时间（默认当前时间）
        });
    }

    /**
     * 回滚迁移
     *
     * 按照依赖关系的反序删除所有创建的表
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('failed_jobs');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 队列系统数据表迁移
 *
 * 创建 Laravel 队列功能所需的 `jobs`、`job_batches` 和 `failed_jobs` 表。
 */
return new class extends Migration
{
    /**
     * 执行数据库迁移。
     *
     * 创建队列任务、批量任务和失败任务相关的表。
     */
    public function up(): void
    {
        // 创建队列任务表 (jobs)
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();                                   // 任务ID，自增主键
            $table->string('queue')->index();               // 队列名称
            $table->longText('payload');                    // 任务数据负载
            $table->unsignedTinyInteger('attempts');        // 任务尝试执行次数
            $table->unsignedInteger('reserved_at')->nullable(); // 任务被保留时间戳
            $table->unsignedInteger('available_at');        // 任务可执行时间戳
            $table->unsignedInteger('created_at');          // 任务创建时间戳
        });

        // 创建批量任务表 (job_batches)
        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();                // 批次ID，主键
            $table->string('name');                         // 批次名称
            $table->integer('total_jobs');                  // 批次中总任务数
            $table->integer('pending_jobs');                // 批次中待处理任务数
            $table->integer('failed_jobs');                 // 批次中失败任务数
            $table->longText('failed_job_ids');             // 失败任务ID列表
            $table->mediumText('options')->nullable();      // 批次配置选项
            $table->integer('cancelled_at')->nullable();    // 批次取消时间戳
            $table->integer('created_at');                  // 批次创建时间戳
            $table->integer('finished_at')->nullable();     // 批次完成时间戳
        });

        // 创建失败任务表 (failed_jobs)
        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();                                   // 失败任务ID，自增主键
            $table->string('uuid')->unique();               // 任务唯一标识符
            $table->text('connection');                     // 队列连接名称
            $table->text('queue');                          // 队列名称
            $table->longText('payload');                    // 任务数据负载
            $table->longText('exception');                  // 任务失败异常信息
            $table->timestamp('failed_at')->useCurrent();   // 任务失败时间
        });
    }

    /**
     * 回滚数据库迁移。
     *
     * 按照创建的逆序删除相关数据表。
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('failed_jobs');
    }
};
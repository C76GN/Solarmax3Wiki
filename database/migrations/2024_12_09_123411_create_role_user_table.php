<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 用户角色关联表迁移
 *
 * 创建用户与角色的多对多关联表
 * 该表是RBAC（基于角色的访问控制）模型中实现用户-角色映射的关键部分
 */
return new class extends Migration
{
    /**
     * 执行迁移
     *
     * 创建用户与角色的中间关联表及其约束
     */
    public function up(): void
    {
        Schema::create('role_user', function (Blueprint $table) {
            $table->id();                                       // 自增主键ID

            // 外键 - 用户ID，关联users表，级联删除
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');                        // 当用户被删除时，相关角色分配也会被删除

            // 外键 - 角色ID，关联roles表，级联删除
            $table->foreignId('role_id')
                ->constrained()
                ->onDelete('cascade');                        // 当角色被删除时，相关用户分配也会被删除

            $table->timestamps();                               // 创建和更新时间戳

            // 确保每个角色对每个用户只分配一次
            $table->unique(['user_id', 'role_id']);             // 用户ID和角色ID的组合必须唯一
        });
    }

    /**
     * 回滚迁移
     *
     * 删除用户角色关联表
     */
    public function down(): void
    {
        Schema::dropIfExists('role_user');
    }
};

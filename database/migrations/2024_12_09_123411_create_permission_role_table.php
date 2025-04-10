<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 权限角色关联表迁移
 *
 * 创建权限与角色的多对多关联表
 * 该表是RBAC（基于角色的访问控制）模型中实现角色-权限映射的关键部分
 */
return new class extends Migration
{
    /**
     * 执行迁移
     *
     * 创建权限与角色的中间关联表及其约束
     */
    public function up(): void
    {
        Schema::create('permission_role', function (Blueprint $table) {
            $table->id();                                           // 自增主键ID

            // 外键 - 权限ID，关联permissions表，级联删除
            $table->foreignId('permission_id')
                ->constrained()
                ->onDelete('cascade');                            // 当权限被删除时，相关关联也会被删除

            // 外键 - 角色ID，关联roles表，级联删除
            $table->foreignId('role_id')
                ->constrained()
                ->onDelete('cascade');                            // 当角色被删除时，相关关联也会被删除

            $table->timestamps();                                   // 创建和更新时间戳

            // 确保每个权限对每个角色只分配一次
            $table->unique(['permission_id', 'role_id']);           // 权限ID和角色ID的组合必须唯一
        });
    }

    /**
     * 回滚迁移
     *
     * 删除权限角色关联表
     */
    public function down(): void
    {
        Schema::dropIfExists('permission_role');
    }
};

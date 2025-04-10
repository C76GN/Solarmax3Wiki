<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 权限表迁移
 *
 * 创建权限管理系统所需的权限表，用于存储系统的各项权限定义
 * 该表是RBAC（基于角色的访问控制）模型的基础组成部分
 */
return new class extends Migration
{
    /**
     * 执行迁移
     *
     * 创建权限表及其相关字段结构
     */
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();                               // 自增主键ID
            $table->string('name')->unique();           // 权限唯一标识符（如：user.create）
            $table->string('display_name');             // 权限显示名称（如：创建用户）
            $table->string('description')->nullable();  // 权限详细描述
            $table->string('group')->nullable();        // 权限分组（如：用户管理、内容管理等）
            $table->timestamps();                       // 创建和更新时间戳
        });
    }

    /**
     * 回滚迁移
     *
     * 删除权限表
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};

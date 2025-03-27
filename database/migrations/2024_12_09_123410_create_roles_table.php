<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 角色表迁移
 * 
 * 创建角色管理系统所需的角色表，用于存储系统中的各类角色定义
 * 该表是RBAC（基于角色的访问控制）模型的核心组成部分
 */
return new class extends Migration
{
    /**
     * 执行迁移
     * 
     * 创建角色表及其相关字段结构
     * 
     * @return void
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();                                   // 自增主键ID
            $table->string('name')->unique();               // 角色唯一标识符（如：admin, editor）
            $table->string('display_name');                 // 角色显示名称（如：管理员，编辑者）
            $table->string('description')->nullable();      // 角色详细描述
            $table->boolean('is_system')->default(false);   // 是否为系统内置角色（默认否）
            $table->timestamps();                           // 创建和更新时间戳
        });
    }

    /**
     * 回滚迁移
     * 
     * 删除角色表
     * 
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
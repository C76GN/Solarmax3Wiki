<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 创建 Spatie Laravel Permission 包所需的核心权限和角色数据表。
 *
 * 此迁移会创建以下表格：
 * - `permissions`：存储所有可分配的权限。
 * - `roles`：存储所有用户角色。
 * - `model_has_permissions`：模型（如用户）与权限的多对多关联表。
 * - `model_has_roles`：模型（如用户）与角色的多对多关联表。
 * - `role_has_permissions`：角色与权限的多对多关联表。
 */
return new class extends Migration
{
    /**
     * 执行数据库迁移。
     *
     * 创建权限、角色及其关联的枢纽（pivot）数据表。
     */
    public function up(): void
    {
        // 获取权限包配置中的表名和列名
        $teams = config('permission.teams');
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
        $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';

        // 检查配置是否已加载，防止运行时错误
        if (empty($tableNames)) {
            throw new \Exception('错误: config/permission.php 未加载。请运行 [php artisan config:clear] 后重试。');
        }
        if ($teams && empty($columnNames['team_foreign_key'] ?? null)) {
            throw new \Exception('错误: config/permission.php 中的 team_foreign_key 未加载。请运行 [php artisan config:clear] 后重试。');
        }

        // 创建权限表
        Schema::create($tableNames['permissions'], static function (Blueprint $table) {
            $table->bigIncrements('id');            // 权限ID，主键
            $table->string('name');                 // 权限名称
            $table->string('guard_name');           // 守卫名称 (e.g., 'web', 'api')
            $table->timestamps();                   // 创建和更新时间戳

            $table->unique(['name', 'guard_name']); // 权限名称和守卫名称组合唯一
        });

        // 创建角色表
        Schema::create($tableNames['roles'], static function (Blueprint $table) use ($teams, $columnNames) {
            $table->bigIncrements('id');            // 角色ID，主键
            // 如果启用了团队功能，添加团队ID列
            if ($teams || config('permission.testing')) {
                $table->unsignedBigInteger($columnNames['team_foreign_key'])->nullable();
                $table->index($columnNames['team_foreign_key'], 'roles_team_foreign_key_index');
            }
            $table->string('name');                 // 角色名称
            $table->string('guard_name');           // 守卫名称
            $table->timestamps();                   // 创建和更新时间戳

            // 根据是否启用团队功能，设置唯一约束
            if ($teams || config('permission.testing')) {
                $table->unique([$columnNames['team_foreign_key'], 'name', 'guard_name']);
            } else {
                $table->unique(['name', 'guard_name']);
            }
        });

        // 创建模型-权限关联表 (多态关联)
        Schema::create($tableNames['model_has_permissions'], static function (Blueprint $table) use ($tableNames, $columnNames, $pivotPermission, $teams) {
            $table->unsignedBigInteger($pivotPermission); // 权限ID
            $table->string('model_type');                   // 模型类型 (e.g., 'App\Models\User')
            $table->unsignedBigInteger($columnNames['model_morph_key']); // 模型ID

            $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_permissions_model_id_model_type_index');

            // 定义权限ID的外键约束
            $table->foreign($pivotPermission)
                ->references('id')
                ->on($tableNames['permissions'])
                ->onDelete('cascade'); // 级联删除

            // 如果启用了团队功能，添加团队ID并设置复合主键
            if ($teams) {
                $table->unsignedBigInteger($columnNames['team_foreign_key']);
                $table->index($columnNames['team_foreign_key'], 'model_has_permissions_team_foreign_key_index');
                $table->primary([$columnNames['team_foreign_key'], $pivotPermission, $columnNames['model_morph_key'], 'model_type'],
                    'model_has_permissions_permission_model_type_primary');
            } else {
                // 未启用团队功能，设置复合主键
                $table->primary([$pivotPermission, $columnNames['model_morph_key'], 'model_type'],
                    'model_has_permissions_permission_model_type_primary');
            }
        });

        // 创建模型-角色关联表 (多态关联)
        Schema::create($tableNames['model_has_roles'], static function (Blueprint $table) use ($tableNames, $columnNames, $pivotRole, $teams) {
            $table->unsignedBigInteger($pivotRole);     // 角色ID
            $table->string('model_type');               // 模型类型
            $table->unsignedBigInteger($columnNames['model_morph_key']); // 模型ID

            $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_roles_model_id_model_type_index');

            // 定义角色ID的外键约束
            $table->foreign($pivotRole)
                ->references('id')
                ->on($tableNames['roles'])
                ->onDelete('cascade'); // 级联删除

            // 如果启用了团队功能，添加团队ID并设置复合主键
            if ($teams) {
                $table->unsignedBigInteger($columnNames['team_foreign_key']);
                $table->index($columnNames['team_foreign_key'], 'model_has_roles_team_foreign_key_index');
                $table->primary([$columnNames['team_foreign_key'], $pivotRole, $columnNames['model_morph_key'], 'model_type'],
                    'model_has_roles_role_model_type_primary');
            } else {
                // 未启用团队功能，设置复合主键
                $table->primary([$pivotRole, $columnNames['model_morph_key'], 'model_type'],
                    'model_has_roles_role_model_type_primary');
            }
        });

        // 创建角色-权限关联表
        Schema::create($tableNames['role_has_permissions'], static function (Blueprint $table) use ($tableNames, $pivotRole, $pivotPermission) {
            $table->unsignedBigInteger($pivotPermission); // 权限ID
            $table->unsignedBigInteger($pivotRole);     // 角色ID

            // 定义权限ID的外键约束
            $table->foreign($pivotPermission)
                ->references('id')
                ->on($tableNames['permissions'])
                ->onDelete('cascade'); // 级联删除

            // 定义角色ID的外键约束
            $table->foreign($pivotRole)
                ->references('id')
                ->on($tableNames['roles'])
                ->onDelete('cascade'); // 级联删除

            // 设置复合主键
            $table->primary([$pivotPermission, $pivotRole], 'role_has_permissions_permission_id_role_id_primary');
        });

        // 清除权限缓存，确保新表生效
        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    /**
     * 回滚数据库迁移。
     *
     * 按照依赖关系的反序删除所有创建的表。
     */
    public function down(): void
    {
        $tableNames = config('permission.table_names');

        if (empty($tableNames)) {
            throw new \Exception('错误: config/permission.php 未找到，或默认值无法合并。请在继续之前发布包配置，或手动删除表格。');
        }

        Schema::drop($tableNames['role_has_permissions']);
        Schema::drop($tableNames['model_has_roles']);
        Schema::drop($tableNames['model_has_permissions']);
        Schema::drop($tableNames['roles']);
        Schema::drop($tableNames['permissions']);
    }
};
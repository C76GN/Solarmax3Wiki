<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // 保留 Auth 用于获取用户 ID
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role; // 保留 Str 用于 fallback
use App\Models\ActivityLog; // 保留 ActivityLog 用于记录操作日志

class RoleController extends Controller
{
    /**
     * 显示角色列表.
     */
    public function index(): InertiaResponse
    {
        // 使用 Policy
        $this->authorize('viewAny', Role::class);

        // 检查具体操作权限，用于前端按钮显隐 (这里用 can 也可以，但通常 authorize 覆盖了)
        // 如果 Policy 的 viewAny 逻辑比 can('role.view') 更复杂，则需要传递
        // $canEdit = Auth::check() && Auth::user()->can('role.edit');
        // $canDelete = Auth::check() && Auth::user()->can('role.delete');
        // 从 Props 获取权限更佳
        $currentUser = Auth::user();
        $canEdit = $currentUser?->can('role.edit') ?? false;
        $canDelete = $currentUser?->can('role.delete') ?? false;

        // 查询 Spatie Role，只选择数据库列
        $roles = Role::withCount('permissions')
            ->select('id', 'name', 'created_at')
            ->latest()
            ->paginate(10)
            ->through(fn ($role) => [
                'id' => $role->id,
                'name' => $role->name,
                // 直接使用 __() 获取翻译
                'display_name' => __('roles.'.$role->name.'.display_name', [], 'zh_CN') ?? ucfirst($role->name),
                'description' => __('roles.'.$role->name.'.description', [], 'zh_CN'), // 可能返回 null
                'permissions_count' => $role->permissions_count,
                'created_at' => $role->created_at,
                'is_system' => $role->name === 'admin',
            ]);

        return Inertia::render('Roles/Index', [
            'roles' => $roles,
            'canEdit' => $canEdit,     // 传递权限状态给前端
            'canDelete' => $canDelete,   // 传递权限状态给前端
        ]);
    }

    /**
     * 显示创建角色的表单.
     */
    public function create(): InertiaResponse
    {
        // 使用 Policy
        $this->authorize('create', Role::class);

        // 获取所有 Spatie 权限 (只获取 id 和 name)
        $allPermissions = Permission::select('id', 'name')->get();

        // 处理权限元数据和分组
        $permissionsData = $allPermissions->map(function ($permission) {
            // 标准化语言文件键名
            $langKeyBase = 'permissions.'.str_replace('.', '_', $permission->name);

            // 尝试从语言文件获取元数据
            $displayName = __($langKeyBase.'.display_name', [], 'zh_CN');
            $description = __($langKeyBase.'.description', [], 'zh_CN');
            $group = __($langKeyBase.'.group', [], 'zh_CN');

            // 如果语言文件没有定义 group，则从 name 推断
            if ($group === $langKeyBase.'.group') { // 检查是否返回了键名本身
                $group = Str::before($permission->name, '.');
                if (empty($group) || $group === $permission->name) {
                    $group = 'other'; // 如果没有点，归入 'other' 分组
                }
            }
            // 如果语言文件没有定义 display_name，则生成一个
            if ($displayName === $langKeyBase.'.display_name') {
                $displayName = ucfirst(str_replace(['.', '_'], ' ', $permission->name));
            }
            // 如果语言文件没有定义 description，则为 null
            if ($description === $langKeyBase.'.description') {
                $description = null;
            }

            return [
                'id' => $permission->id,
                'name' => $permission->name,
                'display_name' => $displayName,
                'description' => $description,
                'group' => $group,
            ];
        })->groupBy('group')->sortKeys(); // 按分组并排序

        return Inertia::render('Roles/Create', [
            'permissions' => $permissionsData,
        ]);
    }

    /**
     * 存储新创建的角色.
     */
    public function store(Request $request): RedirectResponse
    {
        // 使用 Policy
        $this->authorize('create', Role::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z0-9_]+$/', 'unique:roles,name,NULL,id,guard_name,web'], // 添加正则验证标识符格式
            'display_name' => 'required|string|max:255', // 验证，但不存
            'description' => 'nullable|string|max:255', // 验证，但不存
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        // 创建 Spatie Role
        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($validated['permissions']);

        ActivityLog::log('create', $role);

        return redirect()->route('roles.index')
            ->with('flash', ['message' => ['type' => 'success', 'text' => '角色创建成功！']]);
    }

    /**
     * 显示编辑角色的表单.
     */
    public function edit(Role $role): InertiaResponse|RedirectResponse // 返回类型需要包含 RedirectResponse
    {
        // 使用 Policy
        $this->authorize('update', $role); // Policy 的 update 方法会处理 admin 角色

        // 获取所有 Spatie 权限 (只获取 id 和 name)
        $allPermissions = Permission::select('id', 'name')->get();

        // 处理权限元数据和分组 (逻辑与 create 方法一致)
        $permissionsData = $allPermissions->map(function ($permission) {
            $langKeyBase = 'permissions.'.str_replace('.', '_', $permission->name);
            $displayName = __($langKeyBase.'.display_name', [], 'zh_CN');
            $description = __($langKeyBase.'.description', [], 'zh_CN');
            $group = __($langKeyBase.'.group', [], 'zh_CN');
            if ($group === $langKeyBase.'.group') {
                $group = Str::before($permission->name, '.');
                if (empty($group) || $group === $permission->name) {
                    $group = 'other';
                }
            }

            return [
                'id' => $permission->id,
                'name' => $permission->name,
                'display_name' => ($displayName === $langKeyBase.'.display_name')
                    ? ucfirst(str_replace(['.', '_'], ' ', $permission->name))
                    : $displayName,
                'description' => ($description === $langKeyBase.'.description') ? null : $description,
                'group' => $group,
            ];
        })->groupBy('group')->sortKeys();

        return Inertia::render('Roles/Edit', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'display_name' => __('roles.'.$role->name.'.display_name', [], 'zh_CN') ?? ucfirst($role->name),
                'description' => __('roles.'.$role->name.'.description', [], 'zh_CN'),
                'permissions' => $role->permissions()->pluck('id')->toArray(),
                'is_system' => $role->name === 'admin',
            ],
            'permissions' => $permissionsData,
        ]);
    }

    /**
     * 更新角色信息 (主要是权限).
     */
    public function update(Request $request, Role $role): RedirectResponse
    {
        // 使用 Policy
        $this->authorize('update', $role);

        $validated = $request->validate([
            'display_name' => 'required|string|max:255', // 验证但不存
            'description' => 'nullable|string|max:255', // 验证但不存
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        // Policy 的 update 方法已经处理了 admin 角色，所以这里不需要重复检查
        // 但为了更明确的日志或特殊处理，可以保留
        if ($role->name === 'admin') {
            // 保留 admin 的权限不变
            $validated['permissions'] = $role->permissions()->pluck('id')->toArray();
            Log::warning("Attempted to modify permissions for admin role (ID: {$role->id}) by user ".Auth::id().'. Modification prevented by controller logic.');
        }

        // 只同步权限
        $role->syncPermissions($validated['permissions']);

        ActivityLog::log('update', $role);

        return redirect()->route('roles.index')
            ->with('flash', ['message' => ['type' => 'success', 'text' => '角色权限更新成功！']]);
    }

    /**
     * 删除角色.
     */
    public function destroy(Role $role): RedirectResponse
    {
        // 使用 Policy
        $this->authorize('delete', $role); // Policy 已处理 admin 角色

        // 检查用户关联
        if ($role->users()->exists()) {
            return redirect()->route('roles.index')
                ->with('flash', ['message' => ['type' => 'error', 'text' => '无法删除角色，尚有用户关联到此角色。']]);
        }

        ActivityLog::log('delete', $role, ['name' => $role->name]);
        $role->delete();

        return redirect()->route('roles.index')
            ->with('flash', ['message' => ['type' => 'success', 'text' => '角色删除成功！']]);
    }

    // 移除所有辅助方法:
    // getRoleDisplayName, getRoleDescription,
    // getPermissionDisplayName, getPermissionDescription,
    // getPermissionGroup, getPermissionsConfig
}

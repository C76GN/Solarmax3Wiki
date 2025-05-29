<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * 角色管理控制器
 *
 * 此控制器负责处理系统中用户角色的各项操作，包括查看、创建、编辑和删除角色，
 * 以及管理角色与权限的关联。
 */
class RoleController extends Controller
{
    /**
     * 显示所有角色列表。
     *
     * 该方法会查询所有角色，并对每个角色加载其关联的权限数量，
     * 同时根据语言文件为角色生成友好的显示名称和描述。
     *
     * @return InertiaResponse 返回 Inertia 响应，渲染 'Roles/Index' 页面。
     */
    public function index(): InertiaResponse
    {
        // 授权检查：确保当前用户拥有查看任意角色的权限。
        $this->authorize('viewAny', Role::class);

        // 获取当前认证用户，用于判断其是否具有编辑和删除角色的权限，
        // 这些权限状态将传递给前端以控制UI元素的显示。
        $currentUser = Auth::user();
        $canEdit = $currentUser?->can('role.edit') ?? false;
        $canDelete = $currentUser?->can('role.delete') ?? false;

        // 查询所有角色，并计算每个角色拥有的权限数量。
        // 结果按最新创建时间排序，并进行分页处理。
        $roles = Role::withCount('permissions')
            ->select('id', 'name', 'created_at')
            ->latest()
            ->paginate(10)
            ->through(fn ($role) => [
                'id' => $role->id,
                'name' => $role->name,
                // 尝试从 'zh_CN' 语言文件加载角色的显示名称，
                // 如果未找到，则将角色名称的首字母大写作为备用。
                'display_name' => __('roles.'.$role->name.'.display_name', [], 'zh_CN') ?? ucfirst($role->name),
                // 尝试从 'zh_CN' 语言文件加载角色的描述。
                'description' => __('roles.'.$role->name.'.description', [], 'zh_CN'),
                'permissions_count' => $role->permissions_count,
                'created_at' => $role->created_at,
                // 判断角色是否为系统预设的 'admin' 角色。
                'is_system' => $role->name === 'admin',
            ]);

        // 渲染 Inertia 页面，并将角色数据、编辑权限和删除权限传递给前端。
        return Inertia::render('Roles/Index', [
            'roles' => $roles,
            'canEdit' => $canEdit,
            'canDelete' => $canDelete,
        ]);
    }

    /**
     * 显示创建新角色的表单。
     *
     * 该方法会获取系统中所有可用的权限，并对权限进行分组和格式化，
     * 以便在前端界面中以结构化的方式展示给用户进行选择。
     *
     * @return InertiaResponse 返回 Inertia 响应，渲染 'Roles/Create' 页面。
     */
    public function create(): InertiaResponse
    {
        // 授权检查：确保当前用户拥有创建角色的权限。
        $this->authorize('create', Role::class);

        // 获取所有可用的权限，只选择 ID 和名称字段。
        $allPermissions = Permission::select('id', 'name')->get();

        // 遍历所有权限，为其生成友好的显示名称、描述和分组信息。
        $permissionsData = $allPermissions->map(function ($permission) {
            // 构建语言文件键的基础部分，例如 'permissions.wiki_create'。
            $langKeyBase = 'permissions.'.str_replace('.', '_', $permission->name);

            // 尝试从 'zh_CN' 语言文件获取权限的显示名称、描述和分组。
            $displayName = __($langKeyBase.'.display_name', [], 'zh_CN');
            $description = __($langKeyBase.'.description', [], 'zh_CN');
            $group = __($langKeyBase.'.group', [], 'zh_CN');

            // 如果语言文件未定义分组，则尝试从权限名称中推断。
            // 例如 'wiki.create' 会被归类到 'wiki' 组，否则归为 'other'。
            if ($group === $langKeyBase.'.group') {
                $group = Str::before($permission->name, '.');
                if (empty($group) || $group === $permission->name) {
                    $group = 'other';
                }
            }
            // 如果语言文件未定义显示名称，则将权限名称中的点和下划线替换为空格，
            // 并首字母大写作为备用显示名称。
            if ($displayName === $langKeyBase.'.display_name') {
                $displayName = ucfirst(str_replace(['.', '_'], ' ', $permission->name));
            }
            // 如果语言文件未定义描述，则将描述设为 null。
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
        })->groupBy('group') // 根据 'group' 字段对权限进行分组。
            ->sortKeys(); // 对分组键进行排序。

        // 渲染 Inertia 页面，并将格式化后的权限数据传递给前端。
        return Inertia::render('Roles/Create', [
            'permissions' => $permissionsData,
        ]);
    }

    /**
     * 在数据库中存储新创建的角色。
     *
     * 该方法会验证用户提交的角色信息和权限选择，然后创建新的角色并同步其权限。
     * 同时会记录此操作到活动日志中。
     *
     * @param Request $request 包含角色名称、显示名称、描述和权限 ID 数组的 HTTP 请求。
     * @return RedirectResponse 重定向回角色列表页。
     */
    public function store(Request $request): RedirectResponse
    {
        // 授权检查：确保当前用户拥有创建角色的权限。
        $this->authorize('create', Role::class);

        // 验证请求数据。
        // `name` 字段要求是必填字符串，最大50字符，只包含字母、数字和下划线，且在 'web' guard下唯一。
        // `display_name` 必填字符串，最大255字符。
        // `description` 可选字符串，最大255字符。
        // `permissions` 必填数组，数组中的每个元素必须是存在的权限ID。
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z0-9_]+$/', 'unique:roles,name,NULL,id,guard_name,web'],
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        // 创建新的角色实例。
        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web', // 指定角色所属的 guard。
        ]);

        // 将验证后的权限ID同步到新创建的角色。
        $role->syncPermissions($validated['permissions']);

        // 记录角色创建操作到活动日志。
        ActivityLog::log('create', $role);

        // 重定向用户回角色列表页，并附带一个成功的闪存消息。
        return redirect()->route('roles.index')
            ->with('flash', ['message' => ['type' => 'success', 'text' => '角色创建成功！']]);
    }

    /**
     * 显示编辑现有角色的表单。
     *
     * 该方法会获取指定角色的详细信息及其已分配的权限，
     * 同时加载所有可用权限，并对权限进行分组和格式化，
     * 以便在前端界面中展示供用户编辑。
     *
     * @param Role $role Eloquent 路由模型绑定注入的角色实例。
     * @return InertiaResponse|RedirectResponse 返回 Inertia 响应或重定向（如果权限不足）。
     */
    public function edit(Role $role): InertiaResponse|RedirectResponse
    {
        // 授权检查：确保当前用户拥有更新此特定角色的权限。
        $this->authorize('update', $role);

        // 获取所有可用的权限。
        $allPermissions = Permission::select('id', 'name')->get();

        // 处理权限数据，逻辑与 `create` 方法中的权限处理一致，
        // 包括从语言文件获取显示名称、描述和分组，以及处理备用值。
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
                // 根据语言文件是否提供，决定使用语言文件内容或自动生成。
                'display_name' => ($displayName === $langKeyBase.'.display_name')
                    ? ucfirst(str_replace(['.', '_'], ' ', $permission->name))
                    : $displayName,
                // 根据语言文件是否提供，决定使用语言文件内容或设为 null。
                'description' => ($description === $langKeyBase.'.description') ? null : $description,
                'group' => $group,
            ];
        })->groupBy('group')->sortKeys(); // 按分组并排序。

        // 渲染 Inertia 页面，并传递当前角色的详细信息（包括已拥有的权限ID）
        // 以及所有可供选择的权限数据。
        return Inertia::render('Roles/Edit', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                // 从语言文件获取角色的显示名称和描述，处理备用值。
                'display_name' => __('roles.'.$role->name.'.display_name', [], 'zh_CN') ?? ucfirst($role->name),
                'description' => __('roles.'.$role->name.'.description', [], 'zh_CN'),
                // 获取当前角色已拥有的所有权限的 ID 数组。
                'permissions' => $role->permissions()->pluck('id')->toArray(),
                // 再次标记是否为系统预设的 'admin' 角色，用于前端界面逻辑。
                'is_system' => $role->name === 'admin',
            ],
            'permissions' => $permissionsData,
        ]);
    }

    /**
     * 更新现有角色的信息。
     *
     * 该方法会验证用户提交的更新数据，特别是权限列表。
     * 特别注意：对于 'admin' 角色，其权限不允许通过此接口修改，
     * 任何尝试修改 'admin' 角色权限的请求都会被阻止并记录警告日志。
     *
     * @param Request $request 包含角色显示名称、描述和新权限 ID 数组的 HTTP 请求。
     * @param Role $role Eloquent 路由模型绑定注入的角色实例。
     * @return RedirectResponse 重定向回角色列表页。
     */
    public function update(Request $request, Role $role): RedirectResponse
    {
        // 授权检查：确保当前用户拥有更新此特定角色的权限。
        $this->authorize('update', $role);

        // 验证请求数据。
        // `display_name` 和 `description` 的验证规则与创建时类似。
        // `permissions` 必填数组，且每个 ID 必须存在于权限表中。
        $validated = $request->validate([
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        // 特殊处理：如果尝试修改的是 'admin' 角色，则强制其权限列表为当前已有的所有权限。
        // 这意味着 'admin' 角色的权限无法通过 Web 界面被移除或添加。
        if ($role->name === 'admin') {
            $validated['permissions'] = $role->permissions()->pluck('id')->toArray();
            // 记录警告日志，表明有用户尝试修改管理员权限但被系统阻止。
            Log::warning("Attempted to modify permissions for admin role (ID: {$role->id}) by user ".Auth::id().'. Modification prevented by controller logic.');
        }

        // 同步角色的权限。`syncPermissions` 会自动处理添加、移除和保留权限。
        $role->syncPermissions($validated['permissions']);

        // 记录角色更新操作到活动日志。
        ActivityLog::log('update', $role);

        // 重定向用户回角色列表页，并附带一个成功的闪存消息。
        return redirect()->route('roles.index')
            ->with('flash', ['message' => ['type' => 'success', 'text' => '角色权限更新成功！']]);
    }

    /**
     * 从数据库中删除角色。
     *
     * 在删除角色之前，该方法会进行权限检查，并确保没有用户仍关联到此角色。
     * 如果有用户关联，则拒绝删除并给出提示。
     *
     * @param Role $role Eloquent 路由模型绑定注入的角色实例。
     * @return RedirectResponse 重定向回角色列表页。
     */
    public function destroy(Role $role): RedirectResponse
    {
        // 授权检查：确保当前用户拥有删除此特定角色的权限。
        $this->authorize('delete', $role);

        // 检查是否有任何用户关联到此角色。
        // 如果存在关联用户，则无法删除此角色。
        if ($role->users()->exists()) {
            // 重定向回角色列表页，并附带一个错误的闪存消息，提示用户无法删除。
            return redirect()->route('roles.index')
                ->with('flash', ['message' => ['type' => 'error', 'text' => '无法删除角色，尚有用户关联到此角色。']]);
        }

        // 记录角色删除操作到活动日志。
        // 在删除模型前记录其名称，以防模型被删除后无法获取其属性。
        ActivityLog::log('delete', $role, ['name' => $role->name]);

        // 执行角色的删除操作。
        $role->delete();

        // 重定向用户回角色列表页，并附带一个成功的闪存消息。
        return redirect()->route('roles.index')
            ->with('flash', ['message' => ['type' => 'success', 'text' => '角色删除成功！']]);
    }
}
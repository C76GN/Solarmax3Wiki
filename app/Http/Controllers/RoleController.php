<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Inertia\Response as InertiaResponse;

/**
 * 角色管理控制器
 *
 * 负责处理系统角色的CRUD操作，包括角色的创建、编辑、
 * 查看和删除，以及角色权限的分配
 */
class RoleController extends Controller
{
    /**
     * 显示角色列表页面
     *
     * @return Response 角色列表页面
     */
    public function index(): Response
    {
        $roles = Role::with('permissions')
            ->latest()
            ->paginate(10);

        return Inertia::render('Roles/Index', [
            'roles' => $roles,
        ]);
    }

    /**
     * 显示创建角色页面
     *
     * @return Response 创建角色页面
     */
    public function create(): Response
    {
        // 获取所有权限并按组分类
        $permissions = Permission::all()->groupBy('group');

        return Inertia::render('Roles/Create', [
            'permissions' => $permissions,
        ]);
    }

    /**
     * 存储新创建的角色
     *
     * @param  Request  $request  包含角色数据的请求
     * @return RedirectResponse 重定向到角色列表
     */
    public function store(Request $request): RedirectResponse
    {
        // 验证请求数据
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles'],
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        // 创建新角色
        $role = Role::create([
            'name' => $validated['name'],
            'display_name' => $validated['display_name'],
            'description' => $validated['description'],
            'is_system' => false,
        ]);

        // 附加权限到角色
        $role->permissions()->attach($validated['permissions']);

        // 重定向到角色列表并显示成功消息
        return redirect()->route('roles.index')
            ->with('flash', [
                'message' => [
                    'type' => 'success',
                    'text' => '角色创建成功！',
                ],
            ]);
    }

    /**
     * 显示角色详情页面
     *
     * @param  Role  $role  要查看的角色
     * @return Response 角色详情页面
     */
    public function show(Role $role): Response
    {
        return Inertia::render('Roles/Show', [
            'role' => $role->load('permissions'),
        ]);
    }

    /**
     * 显示编辑角色页面
     *
     * @param  Role  $role  要编辑的角色
     * @return Response|RedirectResponse 编辑角色页面或未授权响应
     */
    public function edit(Role $role): Response|RedirectResponse
    {
        // 系统角色不允许编辑
        if ($role->is_system) {
            return $this->unauthorized('系统角色不可编辑');
        }

        // 获取所有权限并按组分类
        $permissions = Permission::all()->groupBy('group');

        return Inertia::render('Roles/Edit', [
            'role' => array_merge($role->toArray(), [
                'permissions' => $role->permissions->pluck('id')->toArray(),
            ]),
            'permissions' => $permissions,
        ]);
    }

    /**
     * 更新指定角色
     *
     * @param  Request  $request  包含更新数据的请求
     * @param  Role  $role  要更新的角色
     * @return RedirectResponse 重定向到角色列表或未授权响应
     */
    public function update(Request $request, Role $role): RedirectResponse|InertiaResponse
    {
        // 系统角色不允许编辑
        if ($role->is_system) {
            return $this->unauthorized('系统角色不可编辑');
        }

        // 验证请求数据
        $validated = $request->validate([
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        // 更新角色信息
        $role->update([
            'display_name' => $validated['display_name'],
            'description' => $validated['description'],
        ]);

        // 同步角色权限
        $role->permissions()->sync($validated['permissions']);

        // 重定向到角色列表并显示成功消息
        return redirect()->route('roles.index')
            ->with('flash', [
                'message' => [
                    'type' => 'success',
                    'text' => '角色更新成功！',
                ],
            ]);
    }

    /**
     * 删除指定角色
     *
     * @param  Role  $role  要删除的角色
     * @return RedirectResponse 重定向到角色列表或未授权响应
     */
    public function destroy(Role $role): RedirectResponse|InertiaResponse
    {
        // 系统角色不允许删除
        if ($role->is_system) {
            return $this->unauthorized('系统角色不可删除');
        }

        // 删除角色
        $role->delete();

        // 重定向到角色列表并显示成功消息
        return redirect()->route('roles.index')
            ->with('flash', [
                'message' => [
                    'type' => 'success',
                    'text' => '角色删除成功！',
                ],
            ]);
    }
}

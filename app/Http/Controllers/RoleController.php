<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')
            ->latest()
            ->paginate(10);

        return Inertia::render('Roles/Index', [
            'roles' => $roles
        ]);
    }

    public function create()
    {
        $permissions = Permission::all()->groupBy('group');
        
        return Inertia::render('Roles/Create', [
            'permissions' => $permissions
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles'],
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'display_name' => $validated['display_name'],
            'description' => $validated['description'],
            'is_system' => false
        ]);

        $role->permissions()->attach($validated['permissions']);

        return redirect()->route('roles.index')
            ->with('flash', [
                'message' => [
                    'type' => 'success',
                    'text' => '角色创建成功！'
                ]
            ]);
    }

    public function show(Role $role)
    {
        return Inertia::render('Roles/Show', [
            'role' => $role->load('permissions')
        ]);
    }

    public function edit(Role $role)
    {
        if ($role->is_system) {
            return $this->unauthorized('系统角色不可编辑');
        }

        $permissions = Permission::all()->groupBy('group');
        
        return Inertia::render('Roles/Edit', [
            'role' => array_merge($role->toArray(), [
                'permissions' => $role->permissions->pluck('id')->toArray()
            ]),
            'permissions' => $permissions
        ]);
    }

    public function update(Request $request, Role $role)
    {
        if ($role->is_system) {
            return $this->unauthorized('系统角色不可编辑');
        }

        $validated = $request->validate([
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        $role->update([
            'display_name' => $validated['display_name'],
            'description' => $validated['description']
        ]);

        $role->permissions()->sync($validated['permissions']);

        return redirect()->route('roles.index')
            ->with('flash', [
                'message' => [
                    'type' => 'success',
                    'text' => '角色更新成功！'
                ]
            ]);
    }

    public function destroy(Role $role)
    {
        if ($role->is_system) {
            return $this->unauthorized('系统角色不可删除');
        }

        $role->delete();

        return redirect()->route('roles.index')
            ->with('flash', [
                'message' => [
                    'type' => 'success',
                    'text' => '角色删除成功！'
                ]
            ]);
    }
}
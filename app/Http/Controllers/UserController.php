<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('roles')
            ->latest()
            ->paginate(10);

        return Inertia::render('Users/Index', [
            'users' => $users
        ]);
    }

    public function edit(User $user)
    {
        $roles = Role::all();
        
        return Inertia::render('Users/Edit', [
            'user' => array_merge($user->toArray(), [
                'roles' => $user->roles->pluck('id')->toArray()
            ]),
            'roles' => $roles
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id'
        ]);

        $user->roles()->sync($validated['roles']);

        return redirect()->route('users.index')
            ->with('flash', [
                'message' => [
                    'type' => 'success',
                    'text' => '用户角色更新成功！'
                ]
            ]);
    }
}
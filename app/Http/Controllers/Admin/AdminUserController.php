<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    public function index()
    {
        $users = User::with('organization')
            ->orderBy('created_at', 'desc')
            ->get();

        $organizations = Organization::where('is_active', true)->orderBy('name')->get();

        return view('admin.users.index', compact('users', 'organizations'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'            => 'required|string|max:255',
            'email'           => 'required|string|email|max:255|unique:users',
            'password'        => 'required|string|min:6|confirmed',
            'organization_id' => 'nullable|exists:organizations,id',
            'is_super_admin'  => 'boolean',
        ]);

        User::create([
            'name'            => $request->name,
            'email'           => $request->email,
            'password'        => Hash::make($request->password),
            'organization_id' => $request->organization_id,
            'is_super_admin'  => $request->boolean('is_super_admin'),
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Usuario creado correctamente.');
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'            => 'required|string|max:255',
            'email'           => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password'        => 'nullable|string|min:6|confirmed',
            'organization_id' => 'nullable|exists:organizations,id',
            'is_super_admin'  => 'boolean',
        ]);

        $data = [
            'name'            => $request->name,
            'email'           => $request->email,
            'organization_id' => $request->organization_id,
            'is_super_admin'  => $request->boolean('is_super_admin'),
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $user)
    {
        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Usuario eliminado correctamente.');
    }
}

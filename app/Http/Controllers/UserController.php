<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        // Admins can view all Ops and Checkers
        if (auth()->guard('admin')->check()) {
            $users = User::whereIn('role', ['ops', 'checker'])->paginate(15);
            return view('admin.users.index', compact('users'));
        }

        // Ops can view all Checkers
        if (auth()->guard('ops')->check()) {
            $users = User::where('role', 'checker')->paginate(15);
            return view('ops.users.index', compact('users'));
        }

        abort(403);
    }

    public function create()
    {
        if (auth()->guard('admin')->check()) {
            return view('admin.users.create');
        }

        if (auth()->guard('ops')->check()) {
            return view('ops.users.create');
        }

        abort(403);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', Rule::in(['ops', 'checker'])],
        ]);

        // Admins can create Ops and Checkers
        if (auth()->guard('admin')->check()) {
            User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
            ]);
            return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
        }

        // Ops can create Checkers
        if (auth()->guard('ops')->check()) {
            $request->validate([
                'role' => [Rule::in(['checker'])],
            ]);
            User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'checker',
            ]);
            return redirect()->route('ops.users.index')->with('success', 'Checker created successfully.');
        }

        abort(403);
    }

    public function edit(User $user)
    {
        // Admins can edit Ops and Checkers
        if (auth()->guard('admin')->check() && in_array($user->role, ['ops', 'checker'])) {
            return view('admin.users.edit', compact('user'));
        }

        // Ops can edit Checkers
        if (auth()->guard('ops')->check() && $user->role === 'checker') {
            return view('ops.users.edit', compact('user'));
        }

        abort(403);
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', Rule::in(['ops', 'checker'])],
        ]);

        // Admins can update Ops and Checkers
        if (auth()->guard('admin')->check() && in_array($user->role, ['ops', 'checker'])) {
            $user->name = $request->name;
            $user->email = $request->email;
            if ($request->password) {
                $user->password = Hash::make($request->password);
            }
            $user->role = $request->role;
            $user->save();
            return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
        }

        // Ops can update Checkers
        if (auth()->guard('ops')->check() && $user->role === 'checker') {
            $request->validate([
                'role' => [Rule::in(['checker'])],
            ]);
            $user->name = $request->name;
            $user->email = $request->email;
            if ($request->password) {
                $user->password = Hash::make($request->password);
            }
            $user->role = 'checker';
            $user->save();
            return redirect()->route('ops.users.index')->with('success', 'Checker updated successfully.');
        }

        abort(403);
    }

    public function destroy(User $user)
    {
        // Admins can delete Ops and Checkers
        if (auth()->guard('admin')->check() && in_array($user->role, ['ops', 'checker'])) {
            $user->delete();
            return redirect()->route('admin.users.index')->with('success', 'User deleted successfully.');
        }

        // Ops can delete Checkers
        if (auth()->guard('ops')->check() && $user->role === 'checker') {
            $user->delete();
            return redirect()->route('ops.users.index')->with('success', 'Checker deleted successfully.');
        }

        abort(403);
    }
}

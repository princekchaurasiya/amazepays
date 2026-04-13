<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Admin panel -- User management.
 */
class UserController extends Controller
{
    /** List users with search + role filtering. */
    public function index(Request $request)
    {
        $query = User::with('roles')->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->role($request->role);
        }

        return Inertia::render('Admin/Users/Index', [
            'users' => $query->paginate(25),
            'filters' => $request->only('search', 'role'),
        ]);
    }

    /** Show a single user with their orders and wallet. */
    public function show(User $user)
    {
        return Inertia::render('Admin/Users/Show', [
            'user' => $user->load(['roles', 'wallet']),
            'orders' => $user->orders()->latest()->take(10)->get(),
        ]);
    }

    /** Update user profile (admin-level changes). */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => "required|email|unique:users,email,{$user->id}",
            'mobile' => "nullable|string|unique:users,mobile,{$user->id}",
        ]);

        $old = $user->only(['name', 'email', 'mobile']);
        $user->update($validated);

        audit('user.updated', $user, $old, $validated);

        return back()->with('success', "User {$user->name} updated.");
    }

    /** Block a user account. */
    public function block(Request $request, User $user)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $user->update([
            'account_locked' => true,
            'account_locked_reason' => $validated['reason'],
            'account_locked_until' => $request->input('until'),
        ]);

        audit('user.blocked', $user, [], $validated);

        return back()->with('success', "User {$user->name} has been blocked.");
    }

    /** Unblock a user account. */
    public function unblock(User $user)
    {
        $user->update([
            'account_locked' => false,
            'account_locked_reason' => null,
            'account_locked_until' => null,
        ]);

        audit('user.unblocked', $user);

        return back()->with('success', "User {$user->name} has been unblocked.");
    }
}

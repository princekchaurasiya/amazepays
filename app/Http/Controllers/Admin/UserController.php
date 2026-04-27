<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Admin panel -- User management.
 */
class UserController extends Controller
{
    /** List users with search + role filtering. */
    public function index(Request $request)
    {
        $query = User::query()
            ->with(['roles', 'authIdentities'])
            ->latest();

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('display_name', 'like', "%{$search}%")
                    ->orWhereMobileLike($search)
                    ->orWhereEmailLike($search);
            });
        }

        if ($request->filled('role')) {
            $query->role($request->role);
        }

        $users = $query->paginate(25)->through(static function (User $u): array {
            return [
                'id' => $u->id,
                'name' => $u->name ?? $u->display_name,
                'email' => $u->email ?? '—',
                'mobile' => $u->mobile,
                'created_at' => $u->created_at?->toIso8601String(),
                'roles' => $u->roles->map(fn ($r) => ['name' => $r->name])->values()->all(),
            ];
        });

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $request->only('search', 'role'),
        ]);
    }

    /** Show a single user with their orders and wallet. */
    public function show(Request $request, User $user)
    {
        $assignableRoles = Role::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->pluck('name')
            ->all();

        if (! $request->user()?->hasRole('super-admin')) {
            $assignableRoles = array_values(array_filter(
                $assignableRoles,
                static fn (string $name) => $name !== 'super-admin'
            ));
        }

        return Inertia::render('Admin/Users/Show', [
            'user' => $user->load(['roles', 'wallet', 'authIdentities']),
            'orders' => $user->orders()->latest()->take(10)->get(),
            'assignableRoles' => $assignableRoles,
            'canAssignRoles' => Gate::allows('users.assign_roles'),
        ]);
    }

    public function syncRoles(Request $request, User $user)
    {
        Gate::authorize('users.assign_roles');

        $validated = $request->validate([
            'roles' => 'nullable|array',
            'roles.*' => ['string', Rule::exists('roles', 'name')->where('guard_name', 'web')],
        ]);

        $names = array_values(array_unique($validated['roles'] ?? []));

        if (in_array('super-admin', $names, true) && ! $request->user()?->hasRole('super-admin')) {
            return back()->withErrors(['roles' => 'Only a super-admin may assign the super-admin role.']);
        }

        if ($user->hasRole('super-admin') && ! in_array('super-admin', $names, true)) {
            $remaining = User::query()->role('super-admin')->where('id', '!=', $user->id)->count();
            if ($remaining < 1) {
                return back()->withErrors(['roles' => 'Cannot remove the last super-admin account.']);
            }
        }

        $old = $user->roles()->pluck('name')->sort()->values()->all();
        $user->syncRoles($names);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $new = collect($names)->sort()->values()->all();
        audit('user.roles_updated', $user, ['roles' => $old], ['roles' => $new]);

        return back()->with('success', 'Roles updated.');
    }

    /** Update user profile (admin-level changes). */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'mobile' => 'nullable|string|max:32',
        ]);

        $old = [
            'name' => $user->name,
            'email' => $user->email,
            'mobile' => $user->mobile,
        ];

        $user->update([
            'display_name' => (string) $validated['name'],
        ]);

        $user->authIdentities()->updateOrCreate(
            ['provider' => 'email', 'identifier' => (string) $validated['email']],
            [
                'display_identifier' => (string) $validated['email'],
                'is_primary' => true,
                'is_verified' => true,
                'verified_at' => now(),
            ]
        );

        if (! empty($validated['mobile'])) {
            $user->authIdentities()->updateOrCreate(
                ['provider' => 'mobile', 'identifier' => (string) $validated['mobile']],
                [
                    'display_identifier' => (string) $validated['mobile'],
                    'is_primary' => false,
                    'is_verified' => true,
                    'verified_at' => now(),
                ]
            );
        }

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

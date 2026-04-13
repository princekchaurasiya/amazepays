<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class B2bPortalController extends Controller
{
    public function placeOrder(Request $request): Response
    {
        $tenant = $this->resolveTenant($request);

        return Inertia::render('Admin/B2B/PlaceOrder', [
            'tenant' => $tenant ? [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
            ] : null,
        ]);
    }

    public function orders(Request $request): Response
    {
        $tenant = $this->resolveTenant($request);
        $orders = collect();

        if ($tenant) {
            $orders = Order::query()
                ->where('tenant_id', $tenant->id)
                ->with(['user:id,name,email'])
                ->latest()
                ->limit(100)
                ->get(['id', 'user_id', 'tenant_id', 'order_status', 'grand_payable_amount', 'created_at', 'order_number']);
        }

        return Inertia::render('Admin/B2B/Orders', [
            'tenant' => $tenant ? [
                'id' => $tenant->id,
                'name' => $tenant->name,
            ] : null,
            'orders' => $orders,
        ]);
    }

    public function team(Request $request): Response
    {
        $tenant = $this->resolveTenant($request);
        $members = collect();

        if ($tenant) {
            $members = $tenant->users()
                ->get(['users.id', 'users.name', 'users.email', 'users.mobile'])
                ->map(function ($u) {
                    return [
                        'id' => $u->id,
                        'name' => $u->name,
                        'email' => $u->email,
                        'mobile' => $u->mobile,
                        'role' => $u->pivot->role ?? null,
                        'is_primary' => (bool) ($u->pivot->is_primary ?? false),
                    ];
                });
        }

        return Inertia::render('Admin/B2B/Team', [
            'tenant' => $tenant ? [
                'id' => $tenant->id,
                'name' => $tenant->name,
            ] : null,
            'members' => $members,
        ]);
    }

    public function wallet(Request $request): Response
    {
        $user = $request->user();
        $tenant = $this->resolveTenant($request);
        $wallet = $user->wallet;

        return Inertia::render('Admin/B2B/Wallet', [
            'tenant' => $tenant ? [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'credit_limit' => $tenant->credit_limit,
                'current_balance' => $tenant->current_balance,
            ] : null,
            'wallet' => $wallet ? [
                'balance' => (float) $wallet->balance,
            ] : ['balance' => 0.0],
        ]);
    }

    private function resolveTenant(Request $request): ?Tenant
    {
        return $request->user()->tenants()->first();
    }
}

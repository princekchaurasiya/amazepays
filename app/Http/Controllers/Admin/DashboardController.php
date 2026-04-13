<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletLoadRequest;
use App\Services\SecurityEventService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private SecurityEventService $securityEvents) {}

    public function index(Request $request): Response
    {
        $this->authorize('dashboard.view');

        $user = $request->user();
        $dashboardType = $this->resolveDashboardType($user);

        if ($dashboardType === 'b2b') {
            return $this->renderB2bDashboard($user);
        }

        if ($dashboardType === 'finance') {
            return $this->renderFinanceDashboard();
        }

        return $this->renderAdminDashboard();
    }

    private function resolveDashboardType(User $user): string
    {
        if ($user->hasAnyRole(['super-admin', 'admin'])) {
            return 'admin';
        }

        if ($user->hasRole('finance')) {
            return 'finance';
        }

        if ($user->hasAnyRole(['b2b-client', 'b2b-operator'])) {
            return 'b2b';
        }

        return 'admin';
    }

    private function renderAdminDashboard(): Response
    {
        $stats = [
            'orders_today' => Order::whereDate('created_at', today())->count(),
            'revenue_today' => (float) Order::whereDate('created_at', today())->sum('grand_payable_amount'),
            'orders_this_week' => Order::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'total_users' => User::count(),
            'new_users_today' => User::whereDate('created_at', today())->count(),
            'pending_loads' => WalletLoadRequest::where('status', 'pending')->count(),
            'total_wallet_balance' => (float) Wallet::sum('balance'),
        ];

        $recentOrders = Order::with(['user'])
            ->latest()
            ->limit(10)
            ->get(['id', 'user_id', 'order_status', 'grand_payable_amount', 'created_at']);

        $revenueChart = Order::whereBetween('created_at', [now()->subDays(29), now()])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as orders, SUM(grand_payable_amount) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $securityOverview = $this->securityEvents->getOverview();

        return Inertia::render('Admin/Dashboard', [
            'dashboardType' => 'admin',
            'stats' => $stats,
            'recentOrders' => $recentOrders,
            'revenueChart' => $revenueChart,
            'security' => $securityOverview,
            'b2b' => null,
        ]);
    }

    private function renderFinanceDashboard(): Response
    {
        $stats = [
            'orders_today' => Order::whereDate('created_at', today())->count(),
            'revenue_today' => (float) Order::whereDate('created_at', today())->sum('grand_payable_amount'),
            'orders_this_week' => Order::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'pending_loads' => WalletLoadRequest::where('status', 'pending')->count(),
            'total_wallet_balance' => (float) Wallet::sum('balance'),
        ];

        $recentOrders = Order::with(['user'])
            ->latest()
            ->limit(15)
            ->get(['id', 'user_id', 'order_status', 'grand_payable_amount', 'created_at']);

        $revenueChart = Order::whereBetween('created_at', [now()->subDays(29), now()])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as orders, SUM(grand_payable_amount) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return Inertia::render('Admin/Dashboard', [
            'dashboardType' => 'finance',
            'stats' => $stats,
            'recentOrders' => $recentOrders,
            'revenueChart' => $revenueChart,
            'security' => [
                'active_threats' => 0,
                'vpn_attempts_today' => 0,
                'blocked_ips' => 0,
                'failed_logins_today' => 0,
                'wallet_fraud_flags' => 0,
            ],
            'b2b' => null,
        ]);
    }

    private function renderB2bDashboard(User $user): Response
    {
        $tenant = $user->tenants()->first();

        $tenantId = $tenant?->id;

        $scoped = fn () => Order::query()->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId));

        $stats = [
            'orders_today' => $scoped()->whereDate('created_at', today())->count(),
            'revenue_today' => (float) $scoped()->whereDate('created_at', today())->sum('grand_payable_amount'),
            'orders_this_week' => $scoped()->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'total_users' => 0,
            'new_users_today' => 0,
            'pending_loads' => 0,
            'total_wallet_balance' => (float) ($user->wallet?->balance ?? 0),
        ];

        $recentOrders = $scoped()
            ->with(['user:id,name,email'])
            ->latest()
            ->limit(10)
            ->get(['id', 'user_id', 'order_status', 'grand_payable_amount', 'created_at']);

        $revenueChart = $scoped()
            ->whereBetween('created_at', [now()->subDays(29), now()])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as orders, SUM(grand_payable_amount) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return Inertia::render('Admin/Dashboard', [
            'dashboardType' => 'b2b',
            'stats' => $stats,
            'recentOrders' => $recentOrders,
            'revenueChart' => $revenueChart,
            'security' => [
                'active_threats' => 0,
                'vpn_attempts_today' => 0,
                'blocked_ips' => 0,
                'failed_logins_today' => 0,
                'wallet_fraud_flags' => 0,
            ],
            'b2b' => [
                'tenant' => $tenant ? [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'slug' => $tenant->slug,
                ] : null,
            ],
        ]);
    }
}

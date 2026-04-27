<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletLoadRequest;
use App\Services\SecurityEventService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
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
            return $this->renderB2bDashboard($user, $request);
        }

        if ($dashboardType === 'finance') {
            return $this->renderFinanceDashboard($request);
        }

        return $this->renderAdminDashboard($request);
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

    /**
     * @return int One of 7, 14, 30, 60, 90
     */
    private function resolveChartDays(Request $request): int
    {
        $allowed = [7, 14, 30, 60, 90];
        $raw = (int) $request->input('chart_days', 30);

        return in_array($raw, $allowed, true) ? $raw : 30;
    }

    /**
     * @return Collection<int, object{date: string, orders: int, revenue: float}>
     */
    private function revenueChartForDays(int $days, ?int $tenantId = null)
    {
        $start = now()->copy()->subDays($days - 1)->startOfDay();
        $end = now()->endOfDay();

        $revenueExpr = Schema::hasColumn('orders', 'grand_payable_amount')
            ? 'SUM(grand_payable_amount)'
            : '(SUM(grand_total_minor) / 100.0)';

        return Order::query()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw("DATE(created_at) as date, COUNT(*) as orders, {$revenueExpr} as revenue")
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    /**
     * @return array<string, string>
     */
    private function adminStatLinks(): array
    {
        $today = today()->toDateString();

        return [
            'orders_today' => route('panel.orders.index', ['date_from' => $today, 'date_to' => $today]),
            'revenue_today' => route('panel.orders.index', ['date_from' => $today, 'date_to' => $today]),
            'total_users' => route('panel.users.index'),
            'wallet_balance' => route('panel.wallets.index'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function financeStatLinks(): array
    {
        $today = today()->toDateString();

        return [
            'orders_today' => route('panel.orders.index', ['date_from' => $today, 'date_to' => $today]),
            'revenue_today' => route('panel.orders.index', ['date_from' => $today, 'date_to' => $today]),
            'pending_loads' => route('panel.wallets.load-requests'),
            'wallet_balance' => route('panel.wallets.index'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function b2bStatLinks(): array
    {
        $today = today()->toDateString();
        $weekStart = now()->copy()->startOfWeek()->toDateString();
        $weekEnd = now()->copy()->endOfWeek()->toDateString();

        return [
            'orders_today' => route('b2b.orders.index', ['date_from' => $today, 'date_to' => $today]),
            'revenue_today' => route('b2b.orders.index', ['date_from' => $today, 'date_to' => $today]),
            'orders_this_week' => route('b2b.orders.index', ['date_from' => $weekStart, 'date_to' => $weekEnd]),
            'b2b_wallet' => route('b2b.wallet.show'),
        ];
    }

    private function renderAdminDashboard(Request $request): Response
    {
        $chartDays = $this->resolveChartDays($request);

        $revenueToday = Schema::hasColumn('orders', 'grand_payable_amount')
            ? (float) Order::whereDate('created_at', today())->sum('grand_payable_amount')
            : ((float) Order::whereDate('created_at', today())->sum('grand_total_minor')) / 100.0;

        $stats = [
            'orders_today' => Order::whereDate('created_at', today())->count(),
            'revenue_today' => $revenueToday,
            'orders_this_week' => Order::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'total_users' => User::count(),
            'new_users_today' => User::whereDate('created_at', today())->count(),
            'pending_loads' => WalletLoadRequest::where('status', 'pending')->count(),
            'total_wallet_balance' => (float) Wallet::sum('balance'),
        ];

        $recentOrders = Order::query()
            ->with(['user.authIdentities'])
            ->latest()
            ->limit(10)
            ->get(['id', 'user_id', 'status', 'grand_total_minor', 'currency', 'created_at']);

        $revenueChart = $this->revenueChartForDays($chartDays);

        $securityOverview = $this->securityEvents->getOverview();

        return Inertia::render('Admin/Dashboard', [
            'dashboardType' => 'admin',
            'chartDays' => $chartDays,
            'statLinks' => $this->adminStatLinks(),
            'stats' => $stats,
            'recentOrders' => $recentOrders,
            'revenueChart' => $revenueChart,
            'security' => $securityOverview,
            'b2b' => null,
        ]);
    }

    private function renderFinanceDashboard(Request $request): Response
    {
        $chartDays = $this->resolveChartDays($request);

        $revenueToday = Schema::hasColumn('orders', 'grand_payable_amount')
            ? (float) Order::whereDate('created_at', today())->sum('grand_payable_amount')
            : ((float) Order::whereDate('created_at', today())->sum('grand_total_minor')) / 100.0;

        $stats = [
            'orders_today' => Order::whereDate('created_at', today())->count(),
            'revenue_today' => $revenueToday,
            'orders_this_week' => Order::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'pending_loads' => WalletLoadRequest::where('status', 'pending')->count(),
            'total_wallet_balance' => (float) Wallet::sum('balance'),
        ];

        $recentOrders = Order::query()
            ->with(['user.authIdentities'])
            ->latest()
            ->limit(15)
            ->get(['id', 'user_id', 'status', 'grand_total_minor', 'currency', 'created_at']);

        $revenueChart = $this->revenueChartForDays($chartDays);

        return Inertia::render('Admin/Dashboard', [
            'dashboardType' => 'finance',
            'chartDays' => $chartDays,
            'statLinks' => $this->financeStatLinks(),
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

    private function renderB2bDashboard(User $user, Request $request): Response
    {
        $chartDays = $this->resolveChartDays($request);

        $tenant = $user->currentTenant();

        $tenantId = $tenant?->id;

        $scoped = fn () => Order::query()->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId));

        $revenueToday = Schema::hasColumn('orders', 'grand_payable_amount')
            ? (float) $scoped()->whereDate('created_at', today())->sum('grand_payable_amount')
            : ((float) $scoped()->whereDate('created_at', today())->sum('grand_total_minor')) / 100.0;

        $stats = [
            'orders_today' => $scoped()->whereDate('created_at', today())->count(),
            'revenue_today' => $revenueToday,
            'orders_this_week' => $scoped()->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'total_users' => 0,
            'new_users_today' => 0,
            'pending_loads' => 0,
            'total_wallet_balance' => (float) ($user->wallet?->balance ?? 0),
        ];

        $recentOrders = $scoped()
            ->with(['user.authIdentities'])
            ->latest()
            ->limit(10)
            ->get(['id', 'user_id', 'status', 'grand_total_minor', 'currency', 'created_at']);

        $revenueChart = $this->revenueChartForDays($chartDays, $tenantId);

        return Inertia::render('Admin/Dashboard', [
            'dashboardType' => 'b2b',
            'chartDays' => $chartDays,
            'statLinks' => $this->b2bStatLinks(),
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

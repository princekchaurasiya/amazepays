<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProviderConnection;
use App\Models\ProviderOrder;
use App\Models\ProviderSyncRun;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProviderDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('providers.view');

        $vouchagramConfigured = (bool) config('vouchagram.send.username') || (bool) config('vouchagram.pull.username');
        $gyftrEnvConfigured = (bool) env('GYFTR_CLIENT_ID');

        $tenantId = 1;
        $providers = [
            [
                'key' => 'woohoo',
                'label' => 'Woohoo',
                'healthy' => (bool) config('woohoo.host') && (bool) config('woohoo.bearer_token'),
                'href' => route('panel.woohoo.index'),
                'note' => null,
                'provider_connection_key' => 'woohoo',
            ],
            [
                'key' => 'vouchagram_gyftr',
                'label' => 'Vouchagram / Gyftr',
                'healthy' => $vouchagramConfigured || $gyftrEnvConfigured,
                'href' => route('panel.vouchagram.index'),
                'note' => 'Same voucher network: Vouchagram is the parent platform (Send/Pull APIs, catalog sync, partner tools); Gyftr is a brand on that network. One dashboard covers both.',
                'provider_connection_key' => 'vouchagram',
            ],
            [
                'key' => 'vd',
                'label' => 'Value Design',
                'healthy' => (bool) config('valuedesign.base_url')
                    && (bool) config('valuedesign.username')
                    && (bool) config('valuedesign.password'),
                'href' => route('panel.value-design.index'),
                'note' => null,
                'provider_connection_key' => 'value_design',
            ],
            [
                'key' => 'lysto',
                'label' => 'Lysto / Athena',
                'healthy' => (bool) config('lysto.base_url') && (bool) config('lysto.api_key'),
                'href' => route('panel.lysto.gift-cards.index'),
                'note' => null,
                'provider_connection_key' => 'lysto_athena',
            ],
        ];

        $connections = ProviderConnection::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('provider', collect($providers)->pluck('provider_connection_key')->unique()->values()->all())
            ->get(['id', 'provider', 'environment', 'is_active', 'last_successful_call_at'])
            ->groupBy('provider');

        $providers = collect($providers)->map(function (array $p) use ($connections) {
            $conn = optional($connections->get($p['provider_connection_key']))->first();
            if (! $conn) {
                $p['connection_present'] = false;
                $p['last_sync_at'] = null;
                $p['pending_queue'] = 0;
                $p['failed_runs'] = 0;
                return $p;
            }

            $p['connection_present'] = true;
            $p['pending_queue'] = (int) ProviderSyncRun::query()
                ->where('connection_id', $conn->id)
                ->whereIn('status', ['queued', 'running'])
                ->count();
            $p['failed_runs'] = (int) ProviderSyncRun::query()
                ->where('connection_id', $conn->id)
                ->where('status', 'failed')
                ->count();
            $p['last_sync_at'] = ProviderSyncRun::query()
                ->where('connection_id', $conn->id)
                ->whereNotNull('completed_at')
                ->orderByDesc('completed_at')
                ->value('completed_at');

            return $p;
        })->values()->all();

        $allConnIds = ProviderConnection::query()->where('tenant_id', $tenantId)->pluck('id')->all();
        $kpis = [
            'totalProviders' => count($providers),
            'pendingQueue' => (int) ProviderSyncRun::query()->whereIn('connection_id', $allConnIds)->whereIn('status', ['queued', 'running'])->count(),
            'failedRuns' => (int) ProviderSyncRun::query()->whereIn('connection_id', $allConnIds)->where('status', 'failed')->count(),
            'lastSuccessfulSync' => ProviderSyncRun::query()->whereIn('connection_id', $allConnIds)->where('status', 'succeeded')->orderByDesc('completed_at')->value('completed_at'),
        ];

        return Inertia::render('Admin/Providers/Index', [
            'providers' => $providers,
            'kpis' => [
                'totalProviders' => (int) $kpis['totalProviders'],
                'pendingQueue' => (int) $kpis['pendingQueue'],
                'failedRuns' => (int) $kpis['failedRuns'],
                'lastSuccessfulSync' => $kpis['lastSuccessfulSync'] ? optional($kpis['lastSuccessfulSync'])->toIso8601String() : null,
            ],
            'order_stats' => [
                'storefront_orders' => Order::query()->count(),
                'kgen_provider_orders' => ProviderOrder::query()->where('provider', 'kgen')->count(),
                'woohoo_products' => Product::query()->where('source_provider', 'woohoo')->count(),
                'vouchagram_products' => Product::query()->whereIn('source_provider', ['vouchagram', 'vouchagram_send', 'vouchagram_pull'])->count(),
                'vd_products' => Product::query()->where('source_provider', 'value_design')->count(),
            ],
        ]);
    }
}

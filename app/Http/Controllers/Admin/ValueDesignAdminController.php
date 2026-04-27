<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProviderConnection;
use App\Models\ProviderSyncRun;
use App\Services\Catalog\CatalogSyncService;
use App\Services\Voucher\ValueDesignService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class ValueDesignAdminController extends Controller
{
    public function __construct(
        private ValueDesignService $valueDesign,
        private CatalogSyncService $catalogSync,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('providers.view');

        $tenantId = 1;
        $connection = ProviderConnection::query()->firstOrCreate(
            ['tenant_id' => $tenantId, 'provider' => 'value_design', 'environment' => 'sandbox'],
            [
                'label' => 'Value Design',
                'is_active' => true,
                'connected_at' => now(),
                'public_config' => [
                    'base_url' => (string) config('valuedesign.base_url'),
                ],
            ]
        );

        $pendingQueue = (int) ProviderSyncRun::query()
            ->where('connection_id', $connection->id)
            ->whereIn('status', ['queued', 'running'])
            ->count();
        $failedJobs = (int) ProviderSyncRun::query()
            ->where('connection_id', $connection->id)
            ->where('status', 'failed')
            ->count();
        $lastSyncAt = ProviderSyncRun::query()
            ->where('connection_id', $connection->id)
            ->whereNotNull('completed_at')
            ->orderByDesc('completed_at')
            ->value('completed_at');
        $syncRuns = ProviderSyncRun::query()
            ->where('connection_id', $connection->id)
            ->orderByDesc('id')
            ->limit(10)
            ->get([
                'id',
                'job_type',
                'status',
                'records_fetched',
                'records_created',
                'records_updated',
                'records_failed',
                'last_error_message',
                'started_at',
                'completed_at',
                'created_at',
            ])
            ->map(fn (ProviderSyncRun $r) => [
                'id' => $r->id,
                'job_type' => (string) $r->job_type,
                'status' => (string) $r->status,
                'records_fetched' => (int) $r->records_fetched,
                'records_created' => (int) $r->records_created,
                'records_updated' => (int) $r->records_updated,
                'records_failed' => (int) $r->records_failed,
                'last_error_message' => $r->last_error_message ? substr((string) $r->last_error_message, 0, 200) : null,
                'started_at' => optional($r->started_at)->toIso8601String(),
                'completed_at' => optional($r->completed_at)->toIso8601String(),
                'created_at' => optional($r->created_at)->toIso8601String(),
            ])
            ->values()
            ->all();

        $productsImported = (int) Product::query()->where('source_provider', 'value_design')->count();

        return Inertia::render('Admin/ValueDesign/Index', [
            'configured' => $this->valueDesign->isConfigured(),
            'distributorId' => config('valuedesign.distributor_id'),
            'kpis' => [
                'productsImported' => $productsImported,
                'pendingQueue' => $pendingQueue,
                'failedJobs' => $failedJobs,
            ],
            'lastSyncAt' => $lastSyncAt ? optional($lastSyncAt)->toIso8601String() : null,
            'syncRuns' => $syncRuns,
            'syncedProducts' => Product::query()
                ->where('source_provider', 'value_design')
                ->orderByDesc('updated_at')
                ->limit(100)
                ->get()
                ->map(fn (Product $p) => [
                    'id' => $p->id,
                    'sku' => $p->sku,
                    'product_name' => $p->name,
                    'denomination' => $p->getAttribute('denomination'),
                    'selling_price' => $p->getAttribute('selling_price'),
                    'show_product' => Schema::hasColumn('products', 'show_product')
                        ? (bool) $p->getAttribute('show_product')
                        : ((string) $p->getAttribute('status') === 'active' && $p->getAttribute('published_at') !== null),
                    'catalog_audience' => Schema::hasColumn('products', 'catalog_audience')
                        ? $p->getAttribute('catalog_audience')
                        : ((bool) $p->getAttribute('is_b2b_only') ? 'b2b' : 'b2c'),
                    'updated_at' => optional($p->updated_at)->toDateTimeString(),
                ])
                ->values(),
            'routes' => [
                'token' => route('panel.value-design.token'),
                'brands' => route('panel.value-design.brands'),
                'stores' => route('panel.value-design.stores'),
                'evc' => route('panel.value-design.evc'),
                'status' => route('panel.value-design.evc-status'),
                'activated' => route('panel.value-design.activated-evc'),
                'wallet' => route('panel.value-design.wallet-balance'),
                'syncCatalog' => route('panel.value-design.sync-catalog'),
            ],
        ]);
    }

    public function token()
    {
        $this->authorize('providers.view');

        return $this->handle(fn () => $this->valueDesign->generateToken());
    }

    public function brands(Request $request)
    {
        $this->authorize('providers.view');
        $validated = $request->validate([
            'token' => 'required|string',
            'brand_code' => 'nullable|string|max:100',
        ]);

        return $this->handle(fn () => $this->valueDesign->getBrands($validated['token'], (string) ($validated['brand_code'] ?? '')));
    }

    public function stores(Request $request)
    {
        $this->authorize('providers.view');
        $validated = $request->validate([
            'token' => 'required|string',
            'brand_code' => 'nullable|string|max:100',
        ]);

        return $this->handle(fn () => $this->valueDesign->getStores($validated['token'], (string) ($validated['brand_code'] ?? '')));
    }

    public function evc(Request $request)
    {
        $this->authorize('providers.view');
        $validated = $request->validate([
            'token' => 'required|string',
            'payload' => 'required|array',
        ]);

        return $this->handle(fn () => $this->valueDesign->getEvc($validated['token'], $validated['payload']));
    }

    public function evcStatus(Request $request)
    {
        $this->authorize('providers.view');
        $validated = $request->validate([
            'token' => 'required|string',
            'order_id' => 'required|string|max:100',
            'request_ref_no' => 'required|string|max:100',
        ]);

        return $this->handle(fn () => $this->valueDesign->getEvcStatus(
            $validated['token'],
            $validated['order_id'],
            $validated['request_ref_no']
        ));
    }

    public function activatedEvc(Request $request)
    {
        $this->authorize('providers.view');
        $validated = $request->validate([
            'token' => 'required|string',
            'order_id' => 'required|string|max:100',
            'request_ref_no' => 'required|string|max:100',
        ]);

        return $this->handle(fn () => $this->valueDesign->getActivatedEvc(
            $validated['token'],
            $validated['order_id'],
            $validated['request_ref_no']
        ));
    }

    public function walletBalance(Request $request)
    {
        $this->authorize('providers.view');
        $validated = $request->validate([
            'token' => 'required|string',
        ]);

        return $this->handle(fn () => $this->valueDesign->getWalletBalance($validated['token']));
    }

    public function syncCatalog()
    {
        $this->authorize('providers.view');

        $tenantId = 1;
        $connection = ProviderConnection::query()->firstOrCreate(
            ['tenant_id' => $tenantId, 'provider' => 'value_design', 'environment' => 'sandbox'],
            ['label' => 'Value Design', 'is_active' => true, 'connected_at' => now()]
        );
        $run = ProviderSyncRun::query()->create([
            'connection_id' => $connection->id,
            'job_type' => 'catalog_sync',
            'status' => 'running',
            'started_at' => now(),
        ]);

        return $this->handle(function () use ($run) {
            $stats = $this->catalogSync->syncProvider('value_design');

            $run->update([
                'status' => 'succeeded',
                'records_created' => (int) ($stats['created'] ?? 0),
                'records_updated' => (int) ($stats['updated'] ?? 0),
                'records_failed' => 0,
                'completed_at' => now(),
            ]);

            return [
                'message' => 'Value Design catalog synced to products table.',
                'stats' => $stats,
            ];
        });
    }

    private function handle(callable $fn)
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $fn(),
            ]);
        } catch (\Throwable $e) {
            // If caller created a ProviderSyncRun, it should be updated by the caller's closure.
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}

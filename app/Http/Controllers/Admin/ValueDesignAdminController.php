<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Catalog\CatalogSyncService;
use App\Services\Voucher\ValueDesignService;
use Illuminate\Http\Request;
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

        return Inertia::render('Admin/ValueDesign/Index', [
            'configured' => $this->valueDesign->isConfigured(),
            'distributorId' => config('services.value_design.distributor_id'),
            'syncedProducts' => Product::query()
                ->where('source_provider', 'value_design')
                ->orderByDesc('updated_at')
                ->limit(100)
                ->get([
                    'id',
                    'sku',
                    'product_name',
                    'denomination',
                    'selling_price',
                    'show_product',
                    'catalog_audience',
                    'updated_at',
                ])
                ->map(fn (Product $p) => [
                    'id' => $p->id,
                    'sku' => $p->sku,
                    'product_name' => $p->product_name ?: $p->name,
                    'denomination' => $p->denomination,
                    'selling_price' => $p->selling_price,
                    'show_product' => (bool) $p->show_product,
                    'catalog_audience' => $p->catalog_audience,
                    'updated_at' => optional($p->updated_at)->toDateTimeString(),
                ])
                ->values(),
            'routes' => [
                'token' => route('admin.value-design.token'),
                'brands' => route('admin.value-design.brands'),
                'stores' => route('admin.value-design.stores'),
                'evc' => route('admin.value-design.evc'),
                'status' => route('admin.value-design.evc-status'),
                'activated' => route('admin.value-design.activated-evc'),
                'wallet' => route('admin.value-design.wallet-balance'),
                'syncCatalog' => route('admin.value-design.sync-catalog'),
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

        return $this->handle(function () {
            $stats = $this->catalogSync->syncProvider('value_design');

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
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Catalog\CatalogSyncService;
use App\Services\Voucher\VouchagramPullProvider;
use App\Services\Voucher\VouchagramSendProvider;
use App\Services\Voucher\VouchagramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class VouchagramController extends Controller
{
    public function __construct(
        private VouchagramService $vouchagram,
        private CatalogSyncService $catalogSync,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('providers.view');

        return Inertia::render('Admin/Vouchagram/Index', [
            'sendConfigured' => $this->isSendConfigured(),
            'pullConfigured' => $this->isPullConfigured(),
        ]);
    }

    public function fetchBrands(Request $request): JsonResponse
    {
        $this->authorize('providers.view');
        $validated = $request->validate([
            'mode' => 'required|string|in:send,pull',
            'brand_code' => 'nullable|string|max:100',
        ]);

        try {
            $mode = $validated['mode'] === 'pull' ? VouchagramService::MODE_PULL : VouchagramService::MODE_SEND;
            $brands = $this->vouchagram->getBrands($mode, $validated['brand_code'] ?? null);
            $safe = $this->sanitizeBrandsForAdminResponse($brands);

            return response()->json(['success' => true, 'data' => $safe]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Strip long / sensitive provider fields from getbrands rows (admin UI only).
     *
     * @param  array<int, mixed>  $brands
     * @return array<int, array<string, mixed>>
     */
    private function sanitizeBrandsForAdminResponse(array $brands): array
    {
        $out = [];
        foreach ($brands as $row) {
            if (! is_array($row)) {
                continue;
            }
            $code = (string) ($row['BrandProductCode'] ?? '');
            if ($code === '') {
                continue;
            }
            $out[] = array_filter([
                'BrandProductCode' => $code,
                'BrandName' => $row['BrandName'] ?? null,
                'Brandtype' => $row['Brandtype'] ?? null,
                'BrandImage' => $row['BrandImage'] ?? null,
                'DenomType' => $row['DenomType'] ?? null,
                'denominationList' => $row['denominationList'] ?? null,
                'MinValue' => $row['MinValue'] ?? null,
                'MaxValue' => $row['MaxValue'] ?? null,
                'stockAvailable' => $row['stockAvailable'] ?? null,
                'Category' => $row['Category'] ?? null,
                'RedemptionType' => $row['RedemptionType'] ?? null,
                'OnlineRedemptionUrl' => $row['OnlineRedemptionUrl'] ?? null,
            ], fn ($v) => $v !== null && $v !== '');
        }

        return $out;
    }

    public function sendVoucher(Request $request): JsonResponse
    {
        $this->authorize('providers.view');
        $validated = $request->validate([
            'brand_product_code' => 'required|string|max:100',
            'external_order_id' => 'required|string|max:50',
            'quantity' => 'required|integer|min:1|max:10',
            'denomination' => 'required|string|max:20',
            'customer_first_name' => 'required|string|max:50',
            'customer_last_name' => 'nullable|string|max:50',
            'email' => 'required|email|max:150',
            'mobile' => 'required|string|max:10',
            'service_type' => 'nullable|string|in:E,V',
        ]);

        $provider = new VouchagramSendProvider($this->vouchagram);
        $result = $provider->placeOrder([
            'brand_product_code' => $validated['brand_product_code'],
            'external_order_id' => $validated['external_order_id'],
            'quantity' => $validated['quantity'],
            'denomination' => $validated['denomination'],
            'customer_first_name' => $validated['customer_first_name'],
            'customer_last_name' => $validated['customer_last_name'] ?? '',
            'email' => $validated['email'],
            'mobile' => $validated['mobile'],
            'service_type' => $validated['service_type'] ?? 'V',
        ]);

        return response()->json([
            'success' => $result->success,
            'status' => $result->status,
            'provider_order_id' => $result->providerOrderId,
            'error' => $result->error,
            'raw' => $result->raw,
        ], $result->success ? 200 : 422);
    }

    public function pullVoucher(Request $request): JsonResponse
    {
        $this->authorize('providers.view');
        $validated = $request->validate([
            'brand_product_code' => 'required|string|max:100',
            'external_order_id' => 'required|string|max:50',
            'quantity' => 'required|integer|min:1|max:10',
            'denomination' => 'required|string|max:20',
        ]);

        $provider = new VouchagramPullProvider($this->vouchagram);
        $result = $provider->placeOrder([
            'brand_product_code' => $validated['brand_product_code'],
            'external_order_id' => $validated['external_order_id'],
            'quantity' => $validated['quantity'],
            'denomination' => $validated['denomination'],
        ]);

        return response()->json([
            'success' => $result->success,
            'status' => $result->status,
            'provider_order_id' => $result->providerOrderId,
            'voucher_code' => $result->voucherCode,
            'pin' => $result->pin,
            'voucher_codes' => $result->voucherCodes,
            'error' => $result->error,
            'raw' => $result->raw,
        ], $result->success ? 200 : 422);
    }

    public function checkSendStatus(Request $request): JsonResponse
    {
        $this->authorize('providers.view');
        $validated = $request->validate([
            'external_order_id' => 'required|string|max:50',
        ]);

        $provider = new VouchagramSendProvider($this->vouchagram);
        $result = $provider->queryOrder($validated['external_order_id']);

        return response()->json([
            'success' => $result->success,
            'status' => $result->status,
            'raw' => $result->raw,
            'error' => $result->error,
        ]);
    }

    public function checkPullStatus(Request $request): JsonResponse
    {
        $this->authorize('providers.view');
        $validated = $request->validate([
            'external_order_id' => 'required|string|max:50',
        ]);

        $provider = new VouchagramPullProvider($this->vouchagram);
        $result = $provider->queryOrder($validated['external_order_id']);

        return response()->json([
            'success' => $result->success,
            'status' => $result->status,
            'raw' => $result->raw,
            'error' => $result->error,
        ]);
    }

    public function checkStock(Request $request): JsonResponse
    {
        $this->authorize('providers.view');
        $validated = $request->validate([
            'mode' => 'required|string|in:send,pull',
            'brand_product_code' => 'required|string|max:100',
            'denomination' => 'required|string|max:20',
        ]);

        try {
            $mode = $validated['mode'] === 'pull' ? VouchagramService::MODE_PULL : VouchagramService::MODE_SEND;
            $data = $this->vouchagram->getStock($mode, $validated['brand_product_code'], $validated['denomination']);

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function getStoreList(Request $request): JsonResponse
    {
        $this->authorize('providers.view');
        $validated = $request->validate([
            'mode' => 'required|string|in:send,pull',
            'brand_product_code' => 'nullable|string|max:100',
            'shop' => 'nullable|string|max:10',
        ]);

        try {
            $mode = $validated['mode'] === 'pull' ? VouchagramService::MODE_PULL : VouchagramService::MODE_SEND;
            $data = $this->vouchagram->getStoreList(
                $mode,
                $validated['brand_product_code'] ?? null,
                $validated['shop'] ?? ''
            );

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function syncCatalog(Request $request): JsonResponse
    {
        $this->authorize('providers.sync');

        try {
            $stats = $this->catalogSync->syncProvider('vouchagram');
            $this->ensureProductUrls();

            return response()->json(['success' => true, 'stats' => $stats]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    private function isSendConfigured(): bool
    {
        $c = config('vouchagram.send', []);

        return ! empty($c['username']) && ! empty($c['password']) && ! empty($c['key']) && ! empty($c['iv']);
    }

    private function isPullConfigured(): bool
    {
        $c = config('vouchagram.pull', []);

        return ! empty($c['username']) && ! empty($c['password']) && ! empty($c['key']) && ! empty($c['iv']);
    }

    private function ensureProductUrls(): void
    {
        Product::query()
            ->where('source_provider', 'vouchagram')
            ->where(function ($q) {
                $q->whereNull('url')->orWhere('url', '');
            })
            ->each(function (Product $p) {
                $base = $p->product_name ?? $p->name ?? $p->sku;
                $slug = Str::slug($base.'-'.$p->sku);
                if ($slug === '') {
                    $slug = 'vg-'.$p->id;
                }
                $p->update(['url' => $slug, 'slug' => $slug]);
            });
    }
}

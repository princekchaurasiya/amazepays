<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\VouchagramCatalogSnapshot;
use App\Models\VouchagramCatalogSnapshotItem;
use App\Services\Catalog\CatalogSyncService;
use App\Services\Voucher\VouchagramPullProvider;
use App\Services\Voucher\VouchagramSendProvider;
use App\Services\Voucher\VouchagramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
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
            'canSyncCatalog' => Gate::allows('providers.sync'),
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
            $safe = $this->dedupeSanitizedBrandsByProductCode(
                $this->sanitizeBrandsForAdminResponse($brands)
            );

            $filter = trim((string) ($validated['brand_code'] ?? ''));
            $filter = $filter === '' ? null : $filter;
            $fetchedAt = now();

            $snapshot = DB::transaction(function () use ($validated, $safe, $filter, $fetchedAt) {
                $snapshot = VouchagramCatalogSnapshot::query()->create([
                    'mode' => $validated['mode'],
                    'brand_product_code_filter' => $filter,
                    'item_count' => count($safe),
                    'fetched_at' => $fetchedAt,
                    'created_by' => Auth::id(),
                ]);

                $ts = now();
                $rows = [];
                foreach ($safe as $item) {
                    $rows[] = [
                        'snapshot_id' => $snapshot->id,
                        'api_mode' => $validated['mode'],
                        'brand_product_code' => $item['BrandProductCode'],
                        'payload' => json_encode($item, JSON_THROW_ON_ERROR),
                        'created_at' => $ts,
                        'updated_at' => $ts,
                    ];
                }
                foreach (array_chunk($rows, 500) as $chunk) {
                    DB::table('vouchagram_catalog_snapshot_items')->insert($chunk);
                }

                return $snapshot->fresh();
            });

            return response()->json([
                'success' => true,
                'data' => $safe,
                'snapshot_id' => $snapshot->id,
                'snapshot_fetched_at' => $snapshot->fetched_at->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function listCatalogSnapshots(Request $request): JsonResponse
    {
        $this->authorize('providers.view');
        $validated = $request->validate([
            'mode' => 'nullable|string|in:send,pull',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = VouchagramCatalogSnapshot::query()->orderByDesc('fetched_at');
        if (! empty($validated['mode'] ?? null)) {
            $query->where('mode', $validated['mode']);
        }

        $perPage = $validated['per_page'] ?? 20;
        $paginator = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $paginator->getCollection()->map(fn (VouchagramCatalogSnapshot $s) => [
                'id' => $s->id,
                'mode' => $s->mode,
                'brand_product_code_filter' => $s->brand_product_code_filter,
                'item_count' => $s->item_count,
                'fetched_at' => $s->fetched_at->toIso8601String(),
            ])->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function showCatalogSnapshot(Request $request, int $snapshot): JsonResponse
    {
        $this->authorize('providers.view');
        $validated = $request->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:500',
        ]);

        $snap = VouchagramCatalogSnapshot::query()->findOrFail($snapshot);
        $perPage = $validated['per_page'] ?? 100;

        $paginator = $snap->items()
            ->orderBy('id')
            ->paginate($perPage);

        $payloadRows = collect($paginator->items())->map(fn (VouchagramCatalogSnapshotItem $item) => $item->payload)->values();

        return response()->json([
            'success' => true,
            'snapshot' => [
                'id' => $snap->id,
                'mode' => $snap->mode,
                'brand_product_code_filter' => $snap->brand_product_code_filter,
                'item_count' => $snap->item_count,
                'fetched_at' => $snap->fetched_at->toIso8601String(),
            ],
            'data' => $payloadRows,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * One row per BrandProductCode per snapshot (last duplicate wins).
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function dedupeSanitizedBrandsByProductCode(array $rows): array
    {
        $byCode = [];
        foreach ($rows as $item) {
            $code = (string) ($item['BrandProductCode'] ?? '');
            if ($code === '') {
                continue;
            }
            $byCode[$code] = $item;
        }

        return array_values($byCode);
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

        $validated = $request->validate([
            'mode' => 'required|string|in:send,pull',
        ]);

        try {
            $provider = $validated['mode'] === 'pull' ? 'vouchagram_pull' : 'vouchagram_send';
            $options = $validated['mode'] === 'pull' ? ['default_show_product' => false] : [];

            $stats = $this->catalogSync->syncProvider($provider, $options);
            $this->ensureProductUrls();

            return response()->json(['success' => true, 'stats' => $stats, 'mode' => $validated['mode']]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Upsert products from a saved catalog snapshot (same mapping as live sync; no extra getBrands call).
     */
    public function syncCatalogFromSnapshot(Request $request): JsonResponse
    {
        $this->authorize('providers.sync');

        $validated = $request->validate([
            'snapshot_id' => 'required|integer|exists:vouchagram_catalog_snapshots,id',
        ]);

        try {
            $stats = $this->catalogSync->syncProviderFromSnapshot((int) $validated['snapshot_id']);
            $this->ensureProductUrls();

            $snapshot = VouchagramCatalogSnapshot::query()->find((int) $validated['snapshot_id']);

            return response()->json([
                'success' => true,
                'stats' => $stats,
                'snapshot_id' => (int) $validated['snapshot_id'],
                'mode' => $snapshot?->mode,
            ]);
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
            ->whereIn('source_provider', ['vouchagram', 'vouchagram_send', 'vouchagram_pull'])
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

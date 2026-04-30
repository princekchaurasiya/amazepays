<?php

namespace App\Jobs\Woohoo;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProviderSyncRun;
use App\Services\Catalog\WoohooBrandEnricher;
use App\Services\Catalog\WoohooCatalogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class SyncWoohooSkuJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $syncRunId,
        public readonly string $sku,
        public readonly int $tenantId,
    ) {}

    public function handle(WoohooCatalogService $woohoo, WoohooBrandEnricher $brandEnricher): void
    {
        /** @var ProviderSyncRun|null $run */
        $run = ProviderSyncRun::query()->find($this->syncRunId);
        if (! $run) {
            return;
        }

        $run->update([
            'status' => 'running',
            'started_at' => now(),
            'last_error_message' => null,
            'records_fetched' => 1,
        ]);

        try {
            $payload = $woohoo->fetchProductDetailsBySku($this->sku);

            [$created, $updated] = $this->upsertFromDetails($payload, $brandEnricher);

            $run->update([
                'status' => 'succeeded',
                'records_created' => $created,
                'records_updated' => $updated,
                'records_skipped' => 0,
                'records_failed' => 0,
                'completed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $run->update([
                'status' => 'failed',
                'records_created' => 0,
                'records_updated' => 0,
                'records_skipped' => 0,
                'records_failed' => 1,
                'last_error_message' => substr($e->getMessage(), 0, 1000),
                'completed_at' => now(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{0:int,1:int} created, updated
     */
    private function upsertFromDetails(array $payload, WoohooBrandEnricher $brandEnricher): array
    {
        $sku = trim($this->sku);
        if ($sku === '') {
            throw new \InvalidArgumentException('empty sku');
        }

        $nameRaw = $payload['name'] ?? ($payload['productName'] ?? '');
        $name = trim(is_scalar($nameRaw) ? (string) $nameRaw : '');
        if ($name === '') {
            $name = 'Woohoo '.$sku;
        }

        $brandRaw = $payload['brandName'] ?? ($payload['brand'] ?? 'Woohoo');
        $brandName = trim(is_scalar($brandRaw) ? (string) $brandRaw : 'Woohoo');
        if ($brandName === '') {
            $brandName = 'Woohoo';
        }

        // Resolve brand from name; enrich from details payload when present (payload wins without wiping).
        $brand = $brandEnricher->resolveAndEnrich($this->tenantId, $brandName, $payload);

        $urlRaw = $payload['url'] ?? null;
        $urlSeed = trim(is_scalar($urlRaw) ? (string) $urlRaw : '');
        $slug = Str::slug($urlSeed !== '' ? $urlSeed : ($name.'-'.$sku));
        if ($slug === '') {
            $slug = 'woohoo-'.$sku;
        }

        $currencyRaw = $payload['currency'] ?? ($payload['currencyCode'] ?? 'INR');
        if (is_array($currencyRaw)) {
            $currencyRaw = $currencyRaw['code'] ?? ($currencyRaw['currency'] ?? 'INR');
        }
        $currency = trim(is_scalar($currencyRaw) ? (string) $currencyRaw : 'INR');
        if ($currency === '') {
            $currency = 'INR';
        }

        $externalProductIdRaw = $payload['id'] ?? ($payload['productId'] ?? '');
        $externalProductId = trim(is_scalar($externalProductIdRaw) ? (string) $externalProductIdRaw : '');

        $exists = Product::query()
            ->where('tenant_id', $this->tenantId)
            ->where('sku', $sku)
            ->exists();

        $product = Product::query()->updateOrCreate(
            ['tenant_id' => $this->tenantId, 'sku' => $sku],
            [
                'brand_id' => $brand->id,
                'name' => $name,
                'slug' => $slug,
                'source_provider' => 'woohoo',
                'source_product_id' => $externalProductId !== '' ? $externalProductId : null,
                'currency' => $currency,
                'status' => 'active',
                'type' => 'gift_card',
                'delivery_mode' => 'digital',
                'is_featured' => false,
                'is_b2b_only' => false,
                'is_b2c_only' => false,
                'display_order' => 0,
                'published_at' => now(),
                // Legacy storefront visibility flag (if present in this schema).
                ...(Schema::hasColumn('products', 'show_product') ? ['show_product' => true] : []),
            ]
        );

        if (Schema::hasTable('product_source_sync') && $externalProductId !== '') {
            DB::table('product_source_sync')->updateOrInsert(
                ['product_id' => $product->id],
                [
                    'provider' => 'woohoo',
                    'external_product_id' => $externalProductId,
                    'external_sku' => $sku,
                    'sync_status' => 'healthy',
                    'last_sync_error' => null,
                    'last_sync_at' => now(),
                    'last_raw_payload' => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        return [$exists ? 0 : 1, $exists ? 1 : 0];
    }
}

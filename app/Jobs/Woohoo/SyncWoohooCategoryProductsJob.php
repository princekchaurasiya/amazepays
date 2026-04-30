<?php

namespace App\Jobs\Woohoo;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProviderSyncRun;
use App\Models\SyncedCategory;
use App\Services\Catalog\WoohooCatalogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class SyncWoohooCategoryProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $syncRunId,
        public readonly int $syncedCategoryId,
        public readonly int $tenantId,
    ) {}

    public function handle(WoohooCatalogService $woohoo): void
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
        ]);

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $failed = 0;

        try {
            /** @var SyncedCategory|null $cat */
            $cat = SyncedCategory::query()->find($this->syncedCategoryId);
            if (! $cat || (string) $cat->external_id === '') {
                throw new \RuntimeException('Synced category missing external_id');
            }

            $products = $woohoo->fetchProductsForCategory((string) $cat->external_id);
            $run->update(['records_fetched' => count($products)]);

            foreach ($products as $item) {
                if (! is_array($item)) {
                    $skipped++;

                    continue;
                }

                try {
                    $result = $this->upsertCatalogItem($item);
                    if ($result === 'created') {
                        $created++;
                    } elseif ($result === 'updated') {
                        $updated++;
                    } else {
                        $skipped++;
                    }
                } catch (\Throwable) {
                    $failed++;
                }
            }

            $run->update([
                'status' => $failed > 0 ? 'failed' : 'succeeded',
                'records_created' => $created,
                'records_updated' => $updated,
                'records_skipped' => $skipped,
                'records_failed' => $failed,
                'completed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $run->update([
                'status' => 'failed',
                'records_created' => $created,
                'records_updated' => $updated,
                'records_skipped' => $skipped,
                'records_failed' => $failed + 1,
                'last_error_message' => substr($e->getMessage(), 0, 1000),
                'completed_at' => now(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $item
     * @return 'created'|'updated'|'skipped'
     */
    private function upsertCatalogItem(array $item): string
    {
        $skuRaw = $item['sku'] ?? '';
        $nameRaw = $item['name'] ?? '';
        $sku = trim(is_scalar($skuRaw) ? (string) $skuRaw : '');
        $name = trim(is_scalar($nameRaw) ? (string) $nameRaw : '');
        if ($sku === '' || $name === '') {
            return 'skipped';
        }

        $brandRaw = $item['brandName'] ?? ($item['brand'] ?? 'Woohoo');
        $brandName = trim(is_scalar($brandRaw) ? (string) $brandRaw : 'Woohoo');
        if ($brandName === '') {
            $brandName = 'Woohoo';
        }

        $brand = Brand::query()->firstOrCreate(
            ['tenant_id' => $this->tenantId, 'slug' => Str::slug($brandName) ?: 'woohoo'],
            [
                'name' => $brandName,
                'status' => 'active',
                'is_featured' => false,
                'display_order' => 0,
                'source_provider' => 'woohoo',
                'source_brand_id' => null,
            ]
        );

        $urlRaw = $item['url'] ?? null;
        $urlSeed = trim(is_scalar($urlRaw) ? (string) $urlRaw : '');
        $slug = Str::slug($urlSeed !== '' ? $urlSeed : ($name.'-'.$sku));
        if ($slug === '') {
            $slug = 'woohoo-'.$sku;
        }

        $sourceIdRaw = $item['id'] ?? ($item['productId'] ?? '');
        $sourceProductId = is_scalar($sourceIdRaw) ? (string) $sourceIdRaw : '';

        $currencyRaw = $item['currency'] ?? 'INR';
        if (is_array($currencyRaw)) {
            $currencyRaw = $currencyRaw['code'] ?? ($currencyRaw['currency'] ?? 'INR');
        }
        $currency = trim(is_scalar($currencyRaw) ? (string) $currencyRaw : 'INR');
        if ($currency === '') {
            $currency = 'INR';
        }

        $exists = Product::query()
            ->where('tenant_id', $this->tenantId)
            ->where('sku', $sku)
            ->exists();

        Product::query()->updateOrCreate(
            ['tenant_id' => $this->tenantId, 'sku' => $sku],
            [
                'brand_id' => $brand->id,
                'name' => $name,
                'slug' => $slug,
                'source_provider' => 'woohoo',
                'source_product_id' => $sourceProductId !== '' ? $sourceProductId : null,
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

        return $exists ? 'updated' : 'created';
    }
}

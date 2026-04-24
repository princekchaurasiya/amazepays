<?php

namespace App\Services\Catalog;

use App\Models\Product;
use App\Models\VouchagramCatalogSnapshot;
use App\Services\Voucher\VouchagramCatalogMapper;
use App\Services\Voucher\VoucherProviderFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Syncs product catalogs from all configured voucher providers into the local DB.
 *
 * Called by `SyncVoucherCatalog` artisan command or from admin panel.
 */
class CatalogSyncService
{
    public function __construct(
        private VoucherProviderFactory $providerFactory,
    ) {}

    /**
     * Sync products from a specific provider (fetches catalog from the provider API).
     *
     * @param  array{default_show_product?: bool|null}  $options
     * @return array{created: int, updated: int, deactivated: int}
     */
    public function syncProvider(string $providerName, array $options = []): array
    {
        $provider = $this->providerFactory->make($providerName);
        $catalog = $provider->fetchCatalog();

        return $this->syncProviderCatalog($providerName, $catalog, $options);
    }

    /**
     * Upsert products from a saved Vouchagram catalog snapshot (no live API call).
     *
     * @return array{created: int, updated: int, deactivated: int}
     */
    public function syncProviderFromSnapshot(int $snapshotId): array
    {
        $snapshot = VouchagramCatalogSnapshot::query()->findOrFail($snapshotId);

        $providerName = $snapshot->mode === 'pull' ? 'vouchagram_pull' : 'vouchagram_send';
        $options = $snapshot->mode === 'pull' ? ['default_show_product' => false] : [];

        $brandRows = [];
        $snapshot->items()->orderBy('id')->chunkById(500, function ($items) use (&$brandRows) {
            foreach ($items as $item) {
                if (is_array($item->payload)) {
                    $brandRows[] = $item->payload;
                }
            }
        });

        $catalog = VouchagramCatalogMapper::mapBrandRowsToCatalogItems($brandRows);

        return $this->syncProviderCatalog($providerName, $catalog, $options);
    }

    /**
     * Apply a normalized catalog array to the products table for one provider.
     *
     * @param  array<int, array<string, mixed>>  $catalog
     * @param  array{default_show_product?: bool|null}  $options
     * @return array{created: int, updated: int, deactivated: int}
     */
    public function syncProviderCatalog(string $providerName, array $catalog, array $options = []): array
    {
        $defaultShowProduct = array_key_exists('default_show_product', $options)
            ? $options['default_show_product']
            : null;

        $created = 0;
        $updated = 0;
        $seenSkus = [];

        DB::transaction(function () use ($catalog, $providerName, $defaultShowProduct, &$created, &$updated, &$seenSkus) {
            foreach ($catalog as $item) {
                $sku = isset($item['sku']) ? (string) $item['sku'] : '';
                if ($sku === '') {
                    continue;
                }
                $seenSkus[] = $sku;

                $product = Product::query()
                    ->where('sku', $sku)
                    ->where('source_provider', $providerName)
                    ->first();

                $imageUrl = $item['image_url'] ?? $item['image'] ?? null;
                $images = null;
                if ($imageUrl) {
                    $images = ['small' => $imageUrl, 'large' => $imageUrl];
                }

                $priceVal = $item['price'] ?? null;
                if ($priceVal === null && isset($item['denomination'])) {
                    $priceVal = json_encode([
                        'type' => 'SLAB',
                        'denominations' => [(float) $item['denomination']],
                        'currency' => $item['currency'] ?? 'INR',
                    ]);
                } elseif (is_array($priceVal)) {
                    $priceVal = json_encode($priceVal);
                }

                $derivedDenom = null;
                $derivedSelling = null;
                if (isset($item['denomination']) && is_numeric($item['denomination'])) {
                    $derivedDenom = (float) $item['denomination'];
                    $derivedSelling = $derivedDenom;
                }
                if ($derivedDenom === null && is_array($item['price'] ?? null)) {
                    $p = $item['price'];
                    if (strtoupper((string) ($p['type'] ?? '')) === 'SLAB') {
                        $slabList = $p['denominations'] ?? $p['values'] ?? [];
                        if (is_array($slabList) && count($slabList) === 1) {
                            $derivedDenom = (float) $slabList[0];
                            $derivedSelling = $derivedDenom;
                        }
                    }
                }
                if (isset($item['selling_price']) && is_numeric($item['selling_price'])) {
                    $derivedSelling = (float) $item['selling_price'];
                }

                $pricingPatch = [];
                if ($derivedDenom !== null) {
                    $pricingPatch['denomination'] = $derivedDenom;
                }
                if ($derivedSelling !== null) {
                    $pricingPatch['selling_price'] = $derivedSelling;
                }
                if (isset($item['mrp']) && is_numeric($item['mrp'])) {
                    $pricingPatch['mrp'] = (float) $item['mrp'];
                }

                if ($product) {
                    foreach (array_keys($pricingPatch) as $col) {
                        $current = $product->getAttribute($col);
                        if ($current !== null && $current !== '') {
                            unset($pricingPatch[$col]);
                        }
                    }
                }

                // Provider-facing fields only — never overwrite admin custom_description / terms / how_to_redeem / product_media.
                $providerPayload = array_filter([
                    'name' => $item['name'] ?? null,
                    'product_name' => $item['name'] ?? null,
                    'description' => $item['description'] ?? null,
                    'price' => $priceVal,
                    'images' => $images,
                    'tnc' => $item['tnc'] ?? $item['terms'] ?? null,
                    'last_synced_at' => now(),
                    'sync_status' => 'synced',
                    'catalog_audience' => Product::defaultCatalogAudienceForSourceProvider($providerName),
                ], fn ($v) => $v !== null);

                $providerPayload = array_merge($providerPayload, $pricingPatch);

                if ($product) {
                    $product->update($providerPayload);
                    if (empty($product->slug) && empty($product->url)) {
                        $slug = $this->generateUniqueSlug($product);
                        $product->update(['slug' => $slug, 'url' => $slug]);
                    }
                    $this->ensureDefaultAudiences($product->id, $providerName);
                    $updated++;
                } else {
                    $createAttrs = array_merge($providerPayload, [
                        'sku' => $sku,
                        'source_provider' => $providerName,
                    ]);
                    if ($defaultShowProduct !== null) {
                        $createAttrs['show_product'] = $defaultShowProduct;
                    }
                    $newProduct = Product::create($createAttrs);
                    $slug = $this->generateUniqueSlug($newProduct);
                    $newProduct->update(['slug' => $slug, 'url' => $slug]);
                    $this->ensureDefaultAudiences($newProduct->id, $providerName);
                    $created++;
                }
            }
        });

        $seenSkus = array_values(array_unique($seenSkus));

        $deactivated = 0;
        if ($seenSkus !== []) {
            $deactivated = Product::where('source_provider', $providerName)
                ->whereNotIn('sku', $seenSkus)
                ->update(['show_product' => false]);
        }

        Log::info("Catalog sync complete for {$providerName}", compact('created', 'updated', 'deactivated'));

        return compact('created', 'updated', 'deactivated');
    }

    /**
     * Sync all configured providers.
     *
     * @return array<string, array{created: int, updated: int, deactivated: int}>
     */
    public function syncAll(): array
    {
        $results = [];
        $providers = config('voucher.providers', ['woohoo', 'kgen', 'value_design', 'lysto']);

        foreach ($providers as $provider) {
            try {
                $results[$provider] = $this->syncProvider($provider);
            } catch (\Throwable $e) {
                Log::error("Catalog sync failed for {$provider}", ['error' => $e->getMessage()]);
                $results[$provider] = ['error' => $e->getMessage()];
            }
        }

        return $results;
    }

    private function ensureDefaultAudiences(int $productId, string $providerName): void
    {
        $count = DB::table('product_audiences')->where('product_id', $productId)->count();
        if ($count > 0) {
            return;
        }

        $aud = Product::defaultCatalogAudienceForSourceProvider($providerName);

        $rows = [];
        if ($aud === Product::CATALOG_AUDIENCE_B2C || $aud === Product::CATALOG_AUDIENCE_BOTH) {
            $rows[] = [
                'product_id' => $productId,
                'audience_type' => 'b2c_public',
                'audience_ref_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        if ($aud === Product::CATALOG_AUDIENCE_B2B || $aud === Product::CATALOG_AUDIENCE_BOTH) {
            $rows[] = [
                'product_id' => $productId,
                'audience_type' => 'b2b_partner',
                'audience_ref_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($rows !== []) {
            DB::table('product_audiences')->insert($rows);
        }
    }

    private function generateUniqueSlug(Product $product): string
    {
        $name = (string) ($product->product_name ?? $product->name ?? '');
        $sku = (string) ($product->sku ?? '');
        $base = trim($name.' '.$sku);
        if ($base === '') {
            $base = 'product'.($product->id ? '-'.$product->id : '');
        }
        $slug = Str::slug($base);
        if ($slug === '') {
            $slug = 'product-'.$product->id;
        }

        $original = $slug;
        $i = 1;
        while (Product::where('id', '!=', $product->id)->where('slug', $slug)->exists()) {
            $slug = $original.'-'.$i++;
        }

        return $slug;
    }
}

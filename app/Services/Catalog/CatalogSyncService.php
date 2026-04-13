<?php

namespace App\Services\Catalog;

use App\Models\Product;
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
     * Sync products from a specific provider.
     *
     * @return array{created: int, updated: int, deactivated: int}
     */
    public function syncProvider(string $providerName): array
    {
        $provider = $this->providerFactory->make($providerName);
        $catalog = $provider->fetchCatalog();

        $created = 0;
        $updated = 0;
        $deactivated = 0;
        $seenSkus = [];

        DB::transaction(function () use ($catalog, $providerName, &$created, &$updated, &$seenSkus) {
            foreach ($catalog as $item) {
                $seenSkus[] = $item['sku'];

                $product = Product::where('sku', $item['sku'])->first();

                $imageUrl = $item['image_url'] ?? $item['image'] ?? null;
                $images = null;
                if ($imageUrl) {
                    $images = ['small' => $imageUrl, 'large' => $imageUrl];
                }

                $priceVal = $item['price'] ?? null;
                if ($priceVal === null && isset($item['denomination'])) {
                    $priceVal = json_encode([
                        'type' => 'SLAB',
                        'values' => [(float) $item['denomination']],
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
                    if (strtoupper((string) ($p['type'] ?? '')) === 'SLAB'
                        && isset($p['values'])
                        && is_array($p['values'])
                        && count($p['values']) === 1) {
                        $derivedDenom = (float) $p['values'][0];
                        $derivedSelling = $derivedDenom;
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
                ], fn ($v) => $v !== null);

                $providerPayload = array_merge($providerPayload, $pricingPatch);

                if ($product) {
                    $product->update($providerPayload);
                    if (empty($product->slug) && empty($product->url)) {
                        $slug = $this->generateUniqueSlug($product);
                        $product->update(['slug' => $slug, 'url' => $slug]);
                    }
                    $updated++;
                } else {
                    $newProduct = Product::create(array_merge($providerPayload, [
                        'sku' => $item['sku'],
                        'source_provider' => $providerName,
                    ]));
                    $slug = $this->generateUniqueSlug($newProduct);
                    $newProduct->update(['slug' => $slug, 'url' => $slug]);
                    $created++;
                }
            }
        });

        $deactivated = Product::where('source_provider', $providerName)
            ->whereNotIn('sku', $seenSkus)
            ->update(['show_product' => false]);

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
        $providers = config('services.voucher_providers', ['woohoo', 'kgen', 'value_design', 'lysto']);

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

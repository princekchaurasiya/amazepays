<?php

namespace App\Services\Catalog;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Persists Woohoo Product API payload into normalized product tables (3NF-friendly).
 *
 * Notes:
 * - Payload schema varies by brand; mapping is defensive.
 * - Writes are wrapped in a DB transaction per product.
 * - Writes are gated by Schema::hasTable() so environments missing migrations won't crash.
 */
final class WoohooNormalizedProductPersister
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function persist(int $productId, string $sku, int $tenantId, array $payload): void
    {
        DB::transaction(function () use ($productId, $sku, $tenantId, $payload) {
            $this->upsertCoreProduct($productId, $sku, $tenantId, $payload);
            $this->upsertSourceSync($productId, $sku, $payload);
            $this->upsertDescriptions($productId, $payload);
            $this->upsertPricing($productId, $payload);
            $this->upsertMedia($productId, $payload);
            $this->ensureGiftPolicy($productId);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function upsertCoreProduct(int $productId, string $sku, int $tenantId, array $payload): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        $name = $this->stringish($payload['name'] ?? ($payload['productName'] ?? null)) ?: ('Woohoo '.$sku);
        $currency = $this->extractCurrencyCode($payload['currency'] ?? ($payload['currencyCode'] ?? null)) ?: 'INR';
        $externalId = $this->stringish($payload['id'] ?? ($payload['productId'] ?? null));

        $brandName = $this->stringish($payload['brandName'] ?? ($payload['brand'] ?? null));
        $brandId = null;
        if ($brandName !== '') {
            /** @var WoohooBrandEnricher $brandEnricher */
            $brandEnricher = app(WoohooBrandEnricher::class);
            $brand = $brandEnricher->resolveAndEnrich($tenantId, $brandName, $payload);
            $brandId = $brand->id;
        }

        $slugSeed = $this->stringish($payload['url'] ?? null) ?: Str::slug($name.'-'.$sku);
        $slug = Str::slug($slugSeed);
        if ($slug === '') {
            $slug = 'woohoo-'.$sku;
        }

        $updates = [
            'name' => $name,
            'slug' => $slug,
            // storefront uses url/slug; keep aligned
            'url' => $slug,
            ...(is_int($brandId) ? ['brand_id' => $brandId] : []),
            'currency' => strtoupper(substr($currency, 0, 3)),
            'source_provider' => 'woohoo',
            'source_product_id' => $externalId !== '' ? $externalId : null,
            // Ensure storefront visibility for synced catalog rows (Phase-3 schema).
            'status' => 'active',
            'published_at' => now(),
            // Legacy visibility flag (some deployments still use it)
            'show_product' => true,
            'updated_at' => now(),
        ];

        // Avoid blowing up on legacy schema variants.
        $columns = Schema::getColumnListing('products');
        $safe = [];
        foreach ($updates as $k => $v) {
            if (in_array($k, $columns, true)) {
                $safe[$k] = $v;
            }
        }

        // Never change tenant_id/sku here; those define the record.
        DB::table('products')->where('id', $productId)->where('tenant_id', $tenantId)->where('sku', $sku)->update($safe);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function upsertSourceSync(int $productId, string $sku, array $payload): void
    {
        if (! Schema::hasTable('product_source_sync')) {
            return;
        }

        $externalId = $this->stringish($payload['id'] ?? ($payload['productId'] ?? null));
        if ($externalId === '') {
            // External id is required by schema; fall back to sku to keep a stable candidate key.
            $externalId = $sku;
        }

        DB::table('product_source_sync')->updateOrInsert(
            ['product_id' => $productId],
            [
                'provider' => 'woohoo',
                'external_product_id' => $externalId,
                'external_sku' => $sku,
                'sync_status' => 'healthy',
                'last_sync_error' => null,
                'last_sync_at' => now(),
                'last_raw_payload' => $this->jsonOrNull($payload),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function upsertDescriptions(int $productId, array $payload): void
    {
        if (! Schema::hasTable('product_descriptions')) {
            return;
        }

        $short = $this->stringish($payload['offerShortDesc'] ?? ($payload['shortDescription'] ?? null));
        $long = $this->stringish($payload['description'] ?? ($payload['longDescription'] ?? null));
        $tnc = $this->stringish($payload['tnc'] ?? ($payload['termsAndConditions'] ?? ($payload['terms_and_conditions'] ?? null)));
        $how = $this->stringish($payload['howToUse'] ?? ($payload['how_to_redeem'] ?? ($payload['howToRedeem'] ?? null)));
        $about = $this->stringish($payload['aboutBrand'] ?? ($payload['brandDescription'] ?? null));

        DB::table('product_descriptions')->updateOrInsert(
            ['product_id' => $productId],
            [
                'short_description' => $short !== '' ? $short : null,
                'long_description' => $long !== '' ? $long : null,
                'terms_and_conditions' => $tnc !== '' ? $tnc : null,
                'how_to_use' => $how !== '' ? $how : null,
                'about_brand' => $about !== '' ? $about : null,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function upsertPricing(int $productId, array $payload): void
    {
        // Try to detect pricing from common Woohoo fields.
        $min = $this->moneyToMinor($payload['minPrice'] ?? Arr::get($payload, 'price.min') ?? null);
        $max = $this->moneyToMinor($payload['maxPrice'] ?? Arr::get($payload, 'price.max') ?? null);

        /** @var list<mixed> $denoms */
        $denoms = [];
        $rawDenoms = Arr::get($payload, 'price.denominations');
        if (is_array($rawDenoms)) {
            $denoms = array_values($rawDenoms);
        }

        // If denominations exist, prefer slab pricing table and clear any range.
        if ($denoms !== [] && Schema::hasTable('product_price_denominations')) {
            if (Schema::hasTable('product_price_ranges')) {
                DB::table('product_price_ranges')->where('product_id', $productId)->delete();
            }

            $currency = $this->extractCurrencyCode($payload['currency'] ?? null) ?: 'INR';
            $rows = [];
            $seen = [];
            $order = 0;
            foreach ($denoms as $d) {
                $minor = $this->moneyToMinor($d);
                if ($minor <= 0) {
                    continue;
                }
                $key = $minor.'|'.$currency;
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $rows[] = [
                    'product_id' => $productId,
                    'amount_minor' => $minor,
                    'currency' => strtoupper(substr($currency, 0, 3)),
                    'display_order' => $order++,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Replace denominations set (idempotent, simple).
            DB::table('product_price_denominations')->where('product_id', $productId)->delete();
            if ($rows !== []) {
                DB::table('product_price_denominations')->insert($rows);
            }

            return;
        }

        // Otherwise range pricing if min/max are present.
        if ($min > 0 && $max > 0 && $max >= $min && Schema::hasTable('product_price_ranges')) {
            if (Schema::hasTable('product_price_denominations')) {
                DB::table('product_price_denominations')->where('product_id', $productId)->delete();
            }

            $currency = $this->extractCurrencyCode($payload['currency'] ?? null) ?: 'INR';
            $step = $this->moneyToMinor(Arr::get($payload, 'price.step') ?? null);
            if ($step <= 0) {
                $step = 100; // default ₹1.00 in minor units
            }

            DB::table('product_price_ranges')->updateOrInsert(
                ['product_id' => $productId],
                [
                    'min_amount_minor' => $min,
                    'max_amount_minor' => $max,
                    'step_amount_minor' => $step,
                    'currency' => strtoupper(substr($currency, 0, 3)),
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function upsertMedia(int $productId, array $payload): void
    {
        if (! Schema::hasTable('product_media')) {
            return;
        }

        $images = $payload['images'] ?? null;
        if (! is_array($images)) {
            return;
        }

        $candidates = [
            ['type' => 'thumbnail', 'key' => 'thumbnail'],
            ['type' => 'hero', 'key' => 'base'],
            ['type' => 'side', 'key' => 'mobile'],
            ['type' => 'gallery', 'key' => 'small'],
        ];

        $rows = [];
        $display = 0;
        foreach ($candidates as $c) {
            $url = $this->stringish($images[$c['key']] ?? null);
            if ($url === '') {
                continue;
            }
            $rows[] = [
                'product_id' => $productId,
                'type' => $c['type'],
                'url' => $url,
                'alt_text' => null,
                'display_order' => $display++,
                'is_primary' => $c['type'] === 'thumbnail',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($rows === []) {
            return;
        }

        // Keep it idempotent: for these known types, replace existing provider media.
        DB::table('product_media')
            ->where('product_id', $productId)
            ->whereIn('type', ['thumbnail', 'hero', 'side', 'gallery'])
            ->delete();

        DB::table('product_media')->insert($rows);
    }

    private function ensureGiftPolicy(int $productId): void
    {
        if (! Schema::hasTable('product_gift_policies')) {
            return;
        }

        DB::table('product_gift_policies')->updateOrInsert(
            ['product_id' => $productId],
            [
                'allow_send_to_recipient' => true,
                'allow_scheduled_delivery' => false,
                'allow_gift_message' => true,
                'gift_message_max_chars' => 500,
                'validity_days' => 365,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    private function stringish(mixed $v): string
    {
        if ($v === null) return '';
        if (is_string($v)) return trim($v);
        if (is_numeric($v)) return trim((string) $v);
        return '';
    }

    private function extractCurrencyCode(mixed $currency): string
    {
        if (is_string($currency)) {
            return trim($currency);
        }
        if (is_array($currency)) {
            $code = $currency['code'] ?? ($currency['currency'] ?? null);
            return $this->stringish($code);
        }
        return '';
    }

    private function moneyToMinor(mixed $value): int
    {
        if ($value === null) return 0;
        if (is_int($value)) return $value * 100;
        if (is_float($value)) return (int) round($value * 100);
        if (is_string($value)) {
            $v = trim($value);
            if ($v === '') return 0;
            // Some providers send "100.00" strings.
            if (is_numeric($v)) {
                return (int) round(((float) $v) * 100);
            }
        }
        return 0;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function jsonOrNull(array $payload): ?string
    {
        try {
            $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            return is_string($json) ? $json : null;
        } catch (\Throwable) {
            return null;
        }
    }
}


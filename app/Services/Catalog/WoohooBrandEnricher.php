<?php

namespace App\Services\Catalog;

use App\Models\Brand;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

final class WoohooBrandEnricher
{
    /**
     * Create/resolve the Woohoo brand (by name) and enrich brand media from product-details payload.
     *
     * Defensive rules:
     * - Payload wins when it provides a value.
     * - Missing payload fields must not wipe existing stored values.
     *
     * @param  array<string, mixed>  $payload
     */
    public function resolveAndEnrich(int $tenantId, string $brandName, array $payload): Brand
    {
        $brandName = trim($brandName);
        if ($brandName === '') {
            $brandName = 'Woohoo';
        }

        $slug = Str::slug($brandName) ?: 'woohoo';

        $brand = Brand::query()->firstOrCreate(
            ['tenant_id' => $tenantId, 'slug' => $slug],
            [
                'name' => $brandName,
                'status' => 'active',
                'is_featured' => false,
                'display_order' => 0,
                'source_provider' => 'woohoo',
                'source_brand_id' => null,
            ]
        );

        $updates = [];

        $logo = $this->extractFirstUrl($payload, [
            'brandLogoUrl',
            'brandLogo',
            'brand_logo',
            'logoUrl',
            'logo',
            'brand.logoUrl',
            'brand.logo',
            'images.brandLogo',
            'images.logo',
        ]);
        if ($logo !== null) {
            $updates['logo_url'] = $logo;
        }

        $hero = $this->extractFirstUrl($payload, [
            'brandHeroImageUrl',
            'brandHeroImage',
            'heroImageUrl',
            'hero_image_url',
            'brand.heroImageUrl',
            'brand.hero',
            'images.brandHero',
            'images.hero',
            // some providers send a single large base image; use only as hero if explicitly provided
        ]);
        if ($hero !== null) {
            $updates['hero_image_url'] = $hero;
        }

        // Apply only when we actually have values; never wipe fields.
        if ($updates !== []) {
            $brand->fill($updates);
            $brand->save();
        }

        return $brand;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  list<string>  $paths
     */
    private function extractFirstUrl(array $payload, array $paths): ?string
    {
        foreach ($paths as $path) {
            $raw = Arr::get($payload, $path);
            if (is_string($raw)) {
                $url = trim($raw);
                if ($url !== '' && $url !== 'null') {
                    return $url;
                }
            }
        }

        return null;
    }
}


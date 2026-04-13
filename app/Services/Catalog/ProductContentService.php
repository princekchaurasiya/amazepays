<?php

namespace App\Services\Catalog;

use App\Helpers\ContentFormatter;
use App\Models\Product;
use App\Models\ProductMedia;

/**
 * Resolves customer-facing product content: admin overrides win, then provider data.
 */
class ProductContentService
{
    public const PLACEHOLDER_IMAGE = 'https://via.placeholder.com/600x400?text=Product';

    /**
     * Single primary image URL for grids and product cards.
     */
    public function resolvePrimaryImageUrl(Product $product): ?string
    {
        $product->loadMissing('productMedia');

        $globalMedia = $product->productMedia
            ->filter(fn (ProductMedia $m) => $m->tenant_id === null)
            ->sortBy('sort_order');

        $hero = $globalMedia->firstWhere('collection', 'hero');
        if ($hero) {
            return $hero->url();
        }

        $firstGallery = $globalMedia->firstWhere('collection', 'gallery');
        if ($firstGallery) {
            return $firstGallery->url();
        }

        $firstAny = $globalMedia->first();
        if ($firstAny) {
            return $firstAny->url();
        }

        if (! empty($product->custom_image) && $product->custom_image !== 'null') {
            return asset('storage/'.$product->custom_image);
        }

        $provider = $this->providerImageUrl($product);

        return $provider ?? self::PLACEHOLDER_IMAGE;
    }

    /**
     * All curated image URLs (empty if none uploaded).
     *
     * @return list<string>
     */
    public function resolveGalleryUrls(Product $product): array
    {
        $product->loadMissing('productMedia');

        $urls = $product->productMedia
            ->filter(fn (ProductMedia $m) => $m->tenant_id === null)
            ->sortBy('sort_order')
            ->map(fn (ProductMedia $m) => $m->url())
            ->values()
            ->all();

        if ($urls !== []) {
            return $urls;
        }

        $one = $this->providerImageUrl($product);
        if ($one !== null) {
            return [$one];
        }

        return [];
    }

    /**
     * Description HTML (admin) or provider plain / HTML.
     */
    public function resolveDescription(Product $product): ?string
    {
        $custom = $product->custom_description ?? null;
        if (filled($custom)) {
            return $custom;
        }

        $desc = $product->description ?? null;

        return filled($desc) ? ContentFormatter::extractDescription($desc) : null;
    }

    /**
     * Terms & conditions HTML (admin) or provider content.
     */
    public function resolveTnc(Product $product): ?string
    {
        $custom = $product->terms_and_conditions ?? null;
        if (filled($custom)) {
            return $custom;
        }

        $tnc = $product->tnc ?? null;

        if (! filled($tnc)) {
            return null;
        }

        if (is_string($tnc)) {
            $decoded = json_decode($tnc, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return ContentFormatter::extractTnc($decoded);
            }
        }

        return ContentFormatter::extractTnc($tnc);
    }

    /**
     * How to redeem HTML (admin only; provider rarely sends this).
     */
    public function resolveHowToRedeem(Product $product): ?string
    {
        $custom = $product->how_to_redeem ?? null;

        return filled($custom) ? (string) $custom : null;
    }

    private function providerImageUrl(Product $product): ?string
    {
        $images = $product->images;
        if (is_array($images) && ! empty($images['small'])) {
            return $images['small'];
        }
        if (is_array($images) && ! empty($images['large'])) {
            return $images['large'];
        }

        return null;
    }
}

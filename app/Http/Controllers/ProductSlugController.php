<?php

namespace App\Http\Controllers;

use App\Helpers\ContentFormatter;
use App\Models\GiftCardTheme;
use App\Models\Product;
use App\Services\Catalog\ProductContentService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class ProductSlugController extends Controller
{
    public function getProductBySlug(Request $request)
    {
        try {
            $product = Product::where('url', $request->slug)->first();
            // dd($product);

            if (! $product) {
                return Inertia::render('Error', [
                    'status' => 404,
                    'message' => 'Product not found.',
                ])->toResponse($request)->setStatusCode(404);
            }

            if (! $product->isListedOnConsumerStorefront()) {
                return Inertia::render('Error', [
                    'status' => 404,
                    'message' => 'Product not found.',
                ])->toResponse($request)->setStatusCode(404);
            }

            // Access attributes first to trigger model accessors (auto-decode JSON fields)
            // Then convert to array - this ensures price, currency, images are properly decoded
            $productDetails = $product->toArray();

            // Override with accessor values to ensure JSON fields are decoded
            $productDetails['price'] = $product->price;
            $productDetails['images'] = $product->images;
            $productDetails['currency'] = $product->currency;
            $productDetails['display_image_url'] = $product->display_image_url;
            $productDetails['card_theme'] = $product->resolveCardTheme();
            $productDetails['default_card_value'] = $this->resolveDefaultCardValue($product->price);

            $content = app(ProductContentService::class);

            // Decode howToUse safely from `cpg` (fallback when no admin content)
            $decodedHowToUse = ! empty($productDetails['how_to_redeem'])
                ? $productDetails['how_to_redeem']
                : (! empty($productDetails['cpg'])
                    ? $this->parseHowToUse($productDetails['cpg'])
                    : 'No how to redeem available.');

            $descriptionData = $content->resolveDescription($product);
            $formattedTncData = $content->resolveTnc($product);
            $resolvedHow = $content->resolveHowToRedeem($product);
            $formatteddecodedHowToUse = $resolvedHow
                ? $resolvedHow
                : ContentFormatter::extractHowToRedeem($decodedHowToUse);

            $giftThemes = collect();
            if (Schema::hasTable('gift_card_themes')) {
                $hasGallery = Schema::hasColumn('gift_card_themes', 'gallery_images');
                $hasThumbPath = Schema::hasColumn('gift_card_themes', 'thumbnail_path');
                $hasPreviewPath = Schema::hasColumn('gift_card_themes', 'preview_image_path');
                $hasThumbUrl = Schema::hasColumn('gift_card_themes', 'thumbnail_url');
                $hasImageUrl = Schema::hasColumn('gift_card_themes', 'image_url');
                $giftThemes = GiftCardTheme::query()
                    ->active()
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get()
                    ->map(static function (GiftCardTheme $theme) use ($hasGallery, $hasThumbPath, $hasPreviewPath, $hasThumbUrl, $hasImageUrl): array {
                        $gallery = [];
                        if ($hasGallery) {
                            $gallery = collect((array) $theme->getAttribute('gallery_images'))
                                ->map(static fn ($path) => GiftCardTheme::resolveMediaUrl((string) $path))
                                ->filter()
                                ->values()
                                ->all();
                        }

                        $legacyPrimary = null;
                        if ($hasThumbPath && is_string($theme->getAttribute('thumbnail_path'))) {
                            $legacyPrimary = GiftCardTheme::resolveMediaUrl((string) $theme->getAttribute('thumbnail_path'));
                        } elseif ($hasPreviewPath && is_string($theme->getAttribute('preview_image_path'))) {
                            $legacyPrimary = GiftCardTheme::resolveMediaUrl((string) $theme->getAttribute('preview_image_path'));
                        } elseif ($hasThumbUrl && is_string($theme->getAttribute('thumbnail_url'))) {
                            $legacyPrimary = GiftCardTheme::resolveMediaUrl((string) $theme->getAttribute('thumbnail_url'));
                        } elseif ($hasImageUrl && is_string($theme->getAttribute('image_url'))) {
                            $legacyPrimary = GiftCardTheme::resolveMediaUrl((string) $theme->getAttribute('image_url'));
                        }

                        if ($gallery === [] && $legacyPrimary) {
                            $gallery = [$legacyPrimary];
                        }
                        $primary = $gallery[0] ?? null;

                        return [
                            'id' => $theme->id,
                            'name' => $theme->name,
                            'slug' => $theme->slug,
                            'thumbnail_url' => $primary,
                            'image_url' => $primary,
                            'gallery_images' => $gallery,
                        ];
                    })
                    ->values();
            }

            return Inertia::render('Storefront/Product', [
                'productDetails' => $productDetails,
                'formattedTncData' => $formattedTncData,
                'descriptionData' => $descriptionData,
                'formatteddecodedHowToUse' => $formatteddecodedHowToUse,
                'giftThemes' => $giftThemes,
                'uiText' => [
                    ...__('storefront.product'),
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Error fetching product by slug: '.$e->getMessage());

            return Inertia::render('Error', [
                'status' => 500,
                'message' => 'Something went wrong. Please try again later.',
            ])->toResponse($request)->setStatusCode(500);
        }
    }

    private function parseHowToUse($cpgString)
    {
        try {
            if (! $cpgString) {
                return null;
            }

            $pattern = '/s:8:"howToUse";s:\d+:"([^"]+)"/';
            if (preg_match($pattern, $cpgString, $matches)) {
                return $matches[1];
            }

            return null;
        } catch (Exception $e) {
            return null;
        }
    }

    private function resolveDefaultCardValue($price): int
    {
        $priceData = (array) $price;
        $type = strtoupper((string) ($priceData['type'] ?? 'RANGE'));

        if ($type === 'SLAB') {
            $denominations = array_map(static fn ($value): float => (float) $value, (array) ($priceData['denominations'] ?? []));
            $denominations = array_filter($denominations, static fn ($value): bool => $value > 0);

            return (int) round($denominations !== [] ? min($denominations) : 0);
        }

        $min = isset($priceData['min']) ? (float) $priceData['min'] : 0;

        return (int) round($min > 0 ? $min : 0);
    }
}

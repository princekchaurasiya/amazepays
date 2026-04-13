<?php

namespace App\Helpers;

use App\Models\Product;
use App\Services\Catalog\ProductContentService;

/**
 * Resolve primary image URL for arrays, stdClass, or Eloquent Product.
 */
final class ProductImageHelper
{
    public static function getProductImage(mixed $product): ?string
    {
        $svc = app(ProductContentService::class);

        if ($product instanceof Product) {
            return $svc->resolvePrimaryImageUrl($product);
        }

        if (is_object($product) && isset($product->id)) {
            $model = Product::query()->find($product->id);
            if ($model) {
                return $svc->resolvePrimaryImageUrl($model);
            }
        }

        if (is_array($product) && ! empty($product['id'])) {
            $model = Product::query()->find((int) $product['id']);
            if ($model) {
                return $svc->resolvePrimaryImageUrl($model);
            }
        }

        if (is_array($product)) {
            if (! empty($product['custom_image']) && $product['custom_image'] !== 'null' && $product['custom_image'] !== 'undefined') {
                return asset('storage/'.$product['custom_image']);
            }
            if (! empty($product['images'])) {
                $images = is_string($product['images']) ? json_decode($product['images'], true) : $product['images'];
                if (is_array($images) && ! empty($images['small']) && $images['small'] !== 'null' && $images['small'] !== 'undefined') {
                    return $images['small'];
                }
                if (is_object($images) && ! empty($images->small) && $images->small !== 'null' && $images->small !== 'undefined') {
                    return $images->small;
                }
            }
        } elseif (is_object($product)) {
            if (! empty($product->custom_image) && $product->custom_image !== 'null' && $product->custom_image !== 'undefined') {
                return asset('storage/'.$product->custom_image);
            }
            if (! empty($product->images)) {
                $images = is_string($product->images) ? json_decode($product->images, true) : $product->images;
                if (is_array($images) && ! empty($images['small']) && $images['small'] !== 'null' && $images['small'] !== 'undefined') {
                    return $images['small'];
                }
                if (is_object($images) && ! empty($images->small) && $images->small !== 'null' && $images->small !== 'undefined') {
                    return $images->small;
                }
            }
        }

        return null;
    }
}

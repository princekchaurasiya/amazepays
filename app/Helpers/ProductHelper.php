<?php

namespace App\Helpers;

class ProductHelper
{
    /**
     * Safely decode JSON string to array
     * Handles cases where value might already be decoded or still be a JSON string
     *
     * @param mixed $value
     * @param mixed $default Default value if decoding fails
     * @return array|mixed
     */
    public static function decodeJson($value, $default = [])
    {
        if (is_null($value)) {
            return $default;
        }

        // If already an array, return as is
        if (is_array($value)) {
            return empty($value) ? $default : $value;
        }

        // If it's a string, try to decode it
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return ($decoded !== null && is_array($decoded)) ? $decoded : $default;
        }

        return $default;
    }

    /**
     * Decode price from JSON string or return as array
     *
     * @param mixed $price
     * @return array|null
     */
    public static function decodePrice($price)
    {
        if (is_null($price)) {
            return null;
        }

        if (is_array($price)) {
            return $price;
        }

        if (is_string($price)) {
            $decoded = json_decode($price, true);
            return $decoded !== null ? $decoded : null;
        }

        return null;
    }

    /**
     * Decode currency from JSON string or return as array
     *
     * @param mixed $currency
     * @return array|null
     */
    public static function decodeCurrency($currency)
    {
        if (is_null($currency)) {
            return null;
        }

        if (is_array($currency)) {
            return $currency;
        }

        if (is_string($currency)) {
            $decoded = json_decode($currency, true);
            return $decoded !== null ? $decoded : null;
        }

        return null;
    }

    /**
     * Decode images from JSON string or return as array
     * Always returns an array (never null)
     *
     * @param mixed $images
     * @return array
     */
    public static function decodeImages($images)
    {
        return self::decodeJson($images, []);
    }

    /**
     * Extract range information from price structure
     * Handles both RANGE and SLAB price types
     *
     * @param array|null $price
     * @param string|null $minPrice Fallback min price
     * @param string|null $maxPrice Fallback max price
     * @return array
     */
    public static function extractRange($price, $minPrice = null, $maxPrice = null)
    {
        if (empty($price) || !is_array($price)) {
            return [
                'min' => $minPrice,
                'max' => $maxPrice,
                'type' => null,
                'denominations' => [],
            ];
        }

        // Check if price has cpg structure (common in Woohoo API for both RANGE and SLAB)
        if (isset($price['cpg']) && is_array($price['cpg'])) {
            foreach ($price['cpg'] as $cpgData) {
                if (is_array($cpgData) && isset($cpgData['min']) && isset($cpgData['max'])) {
                    return [
                        'min' => $cpgData['min'],
                        'max' => $cpgData['max'],
                        'type' => $cpgData['type'] ?? null, // Can be 'RANGE' or 'SLAB'
                        'denominations' => $cpgData['denominations'] ?? [],
                    ];
                }
            }
        }

        // Try to extract range from price structure (direct min/max)
        if (isset($price['min']) && isset($price['max'])) {
            return [
                'min' => $price['min'],
                'max' => $price['max'],
                'type' => $price['type'] ?? $price['price'] ?? null,
                'denominations' => $price['denominations'] ?? [],
            ];
        }

        // Fallback to provided min/max
        return [
            'min' => $minPrice,
            'max' => $maxPrice,
            'type' => null,
            'denominations' => [],
        ];
    }

    /**
     * Get minimum price from range or price structure
     *
     * @param array|null $price
     * @param string|null $minPrice Fallback min price
     * @return string|null
     */
    public static function getMinPrice($price, $minPrice = null)
    {
        $range = self::extractRange($price, $minPrice, null);
        return $range['min'] ?? $minPrice;
    }

    /**
     * Get maximum price from range or price structure
     *
     * @param array|null $price
     * @param string|null $maxPrice Fallback max price
     * @return string|null
     */
    public static function getMaxPrice($price, $maxPrice = null)
    {
        $range = self::extractRange($price, null, $maxPrice);
        return $range['max'] ?? $maxPrice;
    }

    /**
     * Get price type (RANGE, SLAB, etc.) from price structure
     *
     * @param array|null $price
     * @return string|null
     */
    public static function getPriceType($price)
    {
        $range = self::extractRange($price);
        return $range['type'] ?? null;
    }

    /**
     * Get available denominations from price structure
     *
     * @param array|null $price
     * @return array
     */
    public static function getDenominations($price)
    {
        $range = self::extractRange($price);
        return $range['denominations'] ?? [];
    }

    /**
     * Check if price structure has a valid range
     *
     * @param array|null $price
     * @return bool
     */
    public static function hasPriceRange($price)
    {
        $range = self::extractRange($price);
        return !empty($range['min']) && !empty($range['max']);
    }

    /**
     * Check if price uses SLAB pricing
     *
     * @param array|null $price
     * @return bool
     */
    public static function isSlabPricing($price)
    {
        $type = self::getPriceType($price);
        return strtoupper($type ?? '') === 'SLAB';
    }

    /**
     * Check if price uses RANGE pricing
     *
     * @param array|null $price
     * @return bool
     */
    public static function isRangePricing($price)
    {
        $type = self::getPriceType($price);
        return strtoupper($type ?? '') === 'RANGE';
    }

    /**
     * Get formatted price display string
     * Examples: "₹100 - ₹10,000" for RANGE, "₹500, ₹1000" for SLAB
     *
     * @param array|null $price
     * @param string $currencySymbol Currency symbol (default: ₹)
     * @return string|null
     */
    public static function getFormattedPriceRange($price, $currencySymbol = '₹')
    {
        $range = self::extractRange($price);
        $min = $range['min'] ?? null;
        $max = $range['max'] ?? null;
        $type = $range['type'] ?? null;

        if (!$min || !$max) {
            return null;
        }

        // Format numbers with commas
        $formattedMin = number_format((float)$min);
        $formattedMax = number_format((float)$max);

        if (strtoupper($type ?? '') === 'SLAB') {
            // For SLAB, show denominations if available
            $denominations = $range['denominations'] ?? [];
            if (!empty($denominations)) {
                $formattedDenoms = array_map(function($d) use ($currencySymbol) {
                    return $currencySymbol . number_format((float)$d);
                }, $denominations);
                return implode(', ', $formattedDenoms);
            }
            return $currencySymbol . $formattedMin . ' - ' . $currencySymbol . $formattedMax;
        }

        // Default RANGE format
        return $currencySymbol . $formattedMin . ' - ' . $currencySymbol . $formattedMax;
    }

    /**
     * Process product data - decode all JSON fields automatically
     * Can be used on arrays or objects
     *
     * @param array|object $product
     * @return array|object
     */
    public static function processProductData($product)
    {
        if (is_object($product)) {
            // Handle object (like Eloquent model)
            if (isset($product->price)) {
                $product->price = self::decodePrice($product->price);
            }
            if (isset($product->currency)) {
                $product->currency = self::decodeCurrency($product->currency);
            }
            if (isset($product->images)) {
                $product->images = self::decodeImages($product->images);
            }
            return $product;
        }

        if (is_array($product)) {
            // Handle array
            if (isset($product['price'])) {
                $product['price'] = self::decodePrice($product['price']);
            }
            if (isset($product['currency'])) {
                $product['currency'] = self::decodeCurrency($product['currency']);
            }
            if (isset($product['images'])) {
                $product['images'] = self::decodeImages($product['images']);
            }
            return $product;
        }

        return $product;
    }

    /**
     * Process multiple products at once
     *
     * @param array|\Illuminate\Support\Collection $products
     * @return array|\Illuminate\Support\Collection
     */
    public static function processProducts($products)
    {
        if (is_array($products) || $products instanceof \Illuminate\Support\Collection) {
            foreach ($products as $product) {
                self::processProductData($product);
            }
        }
        return $products;
    }
}

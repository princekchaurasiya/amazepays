<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * CheckoutHelper - Manages checkout data using cache instead of sessions
 *
 * This approach:
 * - Reduces database load (billing data not stored until payment succeeds)
 * - Reduces server load (cache is faster than DB, no session overhead)
 * - Enables horizontal scaling (no server-side session state)
 * - Maintains security (payment amounts always validated from DB)
 *
 * Architecture:
 * 1. Order data → Database (source of truth for amounts)
 * 2. Billing info → Cache (temporary, 1 hour TTL)
 * 3. Form state → Client localStorage (UX only, rehydrate on page load)
 */
class CheckoutHelper
{
    /**
     * Cache TTL in seconds (1 hour)
     * After this time, user needs to re-enter billing info
     */
    const CACHE_TTL = 3600;

    /**
     * Cache key prefix for billing data
     */
    const BILLING_CACHE_PREFIX = 'checkout:billing:';

    /**
     * Cache key prefix for checkout metadata
     */
    const CHECKOUT_CACHE_PREFIX = 'checkout:meta:';

    /**
     * Store billing information in cache
     *
     * @param  int  $orderId  The order ID (unique identifier)
     * @param  array  $billingData  Billing information
     */
    public static function storeBillingData(int $orderId, array $billingData): bool
    {
        try {
            $cacheKey = self::BILLING_CACHE_PREFIX.$orderId;

            // Sanitize and validate data
            $sanitizedData = self::sanitizeBillingData($billingData);

            // Store in cache with TTL
            Cache::put($cacheKey, $sanitizedData, self::CACHE_TTL);

            Log::info('Billing data cached', [
                'order_id' => $orderId,
                'cache_key' => $cacheKey,
                'ttl' => self::CACHE_TTL,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to cache billing data', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Retrieve billing information from cache
     *
     * @param  int  $orderId  The order ID
     * @return array|null Billing data or null if not found/expired
     */
    public static function getBillingData(int $orderId): ?array
    {
        try {
            $cacheKey = self::BILLING_CACHE_PREFIX.$orderId;
            $data = Cache::get($cacheKey);

            if ($data) {
                Log::info('Billing data retrieved from cache', [
                    'order_id' => $orderId,
                    'cache_key' => $cacheKey,
                ]);
            }

            return $data;
        } catch (\Exception $e) {
            Log::error('Failed to retrieve billing data from cache', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Store checkout metadata (discount codes, applied coupons, etc.)
     *
     * @param  int  $orderId  The order ID
     * @param  array  $metadata  Checkout metadata
     */
    public static function storeCheckoutMetadata(int $orderId, array $metadata): bool
    {
        try {
            $cacheKey = self::CHECKOUT_CACHE_PREFIX.$orderId;
            Cache::put($cacheKey, $metadata, self::CACHE_TTL);

            Log::info('Checkout metadata cached', [
                'order_id' => $orderId,
                'metadata_keys' => array_keys($metadata),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to cache checkout metadata', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Retrieve checkout metadata from cache
     *
     * @param  int  $orderId  The order ID
     * @return array|null Metadata or null if not found/expired
     */
    public static function getCheckoutMetadata(int $orderId): ?array
    {
        try {
            $cacheKey = self::CHECKOUT_CACHE_PREFIX.$orderId;

            return Cache::get($cacheKey);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve checkout metadata', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Clear all cached data for an order (call after successful payment)
     *
     * @param  int  $orderId  The order ID
     */
    public static function clearOrderCache(int $orderId): bool
    {
        try {
            $billingKey = self::BILLING_CACHE_PREFIX.$orderId;
            $metadataKey = self::CHECKOUT_CACHE_PREFIX.$orderId;

            Cache::forget($billingKey);
            Cache::forget($metadataKey);

            Log::info('Order cache cleared', [
                'order_id' => $orderId,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to clear order cache', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Sanitize billing data to prevent XSS and ensure data integrity
     *
     * @param  array  $data  Raw billing data
     * @return array Sanitized data
     */
    private static function sanitizeBillingData(array $data): array
    {
        return [
            'billing_name' => isset($data['billing_name']) ? strip_tags(trim($data['billing_name'])) : null,
            'billing_email' => isset($data['billing_email']) ? filter_var($data['billing_email'], FILTER_SANITIZE_EMAIL) : null,
            'billing_tel' => isset($data['billing_tel']) ? preg_replace('/[^0-9]/', '', $data['billing_tel']) : null,
            'billing_zip' => isset($data['billing_zip']) ? preg_replace('/[^0-9]/', '', $data['billing_zip']) : null,
            'billing_address' => isset($data['billing_address']) ? strip_tags(trim($data['billing_address'])) : null,
            'billing_address_two' => isset($data['billing_address_two']) ? strip_tags(trim($data['billing_address_two'])) : null,
            'billing_city' => isset($data['billing_city']) ? strip_tags(trim($data['billing_city'])) : null,
            'billing_state' => isset($data['billing_state']) ? strip_tags(trim($data['billing_state'])) : null,
            'billing_country' => isset($data['billing_country']) ? strip_tags(trim($data['billing_country'])) : null,
            'billing_gst_number' => isset($data['billing_gst_number']) ? strtoupper(trim($data['billing_gst_number'])) : null,
            'cached_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Get cache statistics for monitoring
     *
     * @return array Cache stats
     */
    public static function getCacheStats(): array
    {
        return [
            'ttl_seconds' => self::CACHE_TTL,
            'ttl_minutes' => self::CACHE_TTL / 60,
            'cache_driver' => config('cache.default'),
            'billing_prefix' => self::BILLING_CACHE_PREFIX,
            'metadata_prefix' => self::CHECKOUT_CACHE_PREFIX,
        ];
    }
}

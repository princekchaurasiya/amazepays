<?php

namespace App\Services\Voucher;

use App\Contracts\StockCheckResult;
use App\Contracts\VoucherOrderResult;
use App\Contracts\VoucherProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * EZ Pin Voucher Provider
 *
 * NOTE: API documentation not yet received (as of Phase 0).
 * This is a stub implementation that will be completed once EZ Pin provides:
 * - API base URL
 * - Authentication method (API Key / OAuth2 / HMAC)
 * - Order placement endpoint and payload format
 * - Catalog endpoint
 * - Voucher code delivery method (sync response / webhook callback)
 * - Status webhook payload format
 *
 * TODO: Update all methods once API docs received. ETA: 7-10 days from provider request.
 */
class EZPinProvider implements VoucherProviderInterface
{
    private array $config;

    public function __construct(array $credentials = [])
    {
        $this->config = empty($credentials) ? [
            'api_url' => config('services.ezpin.api_url'),
            'api_key' => config('services.ezpin.api_key'),
            'api_secret' => config('services.ezpin.api_secret'),
        ] : $credentials;
    }

    public function getName(): string
    {
        return 'ezpin';
    }

    public function supportsIncrementalSync(): bool
    {
        return false; // Update once docs confirmed
    }

    public function isAsyncFulfillment(): bool
    {
        return true; // Assume async until docs confirmed
    }

    public function placeOrder(array $orderData): VoucherOrderResult
    {
        $this->assertConfigured();

        try {
            // TODO: Replace with actual EZ Pin API endpoint and payload format
            $response = Http::withHeaders($this->getHeaders())
                ->timeout(15)
                ->post("{$this->config['api_url']}/orders", [
                    'product_code' => $orderData['sku'],
                    'quantity' => $orderData['quantity'] ?? 1,
                    'reference_id' => $orderData['order_id'],
                    // TODO: Add additional fields per EZ Pin docs
                ]);

            if ($response->successful()) {
                $data = $response->json();

                return new VoucherOrderResult(
                    success: true,
                    status: 'pending',
                    providerOrderId: $data['order_id'] ?? $data['id'] ?? null,
                    raw: $data,
                );
            }

            throw new \RuntimeException('EZ Pin API error: '.$response->body());
        } catch (\Exception $e) {
            Log::error('EZPin placeOrder failed', ['error' => $e->getMessage()]);

            return new VoucherOrderResult(success: false, status: 'failed', error: $e->getMessage());
        }
    }

    public function queryOrder(string $providerOrderId): VoucherOrderResult
    {
        $this->assertConfigured();

        try {
            // TODO: Replace with actual EZ Pin status endpoint
            $response = Http::withHeaders($this->getHeaders())
                ->get("{$this->config['api_url']}/orders/{$providerOrderId}");

            if ($response->successful()) {
                $data = $response->json();

                // TODO: Map EZ Pin response fields to VoucherOrderResult
                return new VoucherOrderResult(
                    success: true,
                    status: $this->mapStatus($data['status'] ?? ''),
                    providerOrderId: $providerOrderId,
                    voucherCode: $data['voucher_code'] ?? $data['code'] ?? null,
                    pin: $data['pin'] ?? null,
                    raw: $data,
                );
            }

            throw new \RuntimeException('EZ Pin status query failed: '.$response->body());
        } catch (\Exception $e) {
            return new VoucherOrderResult(success: false, status: 'failed', error: $e->getMessage());
        }
    }

    public function fetchCatalog(): array
    {
        $this->assertConfigured();

        try {
            // TODO: Replace with actual EZ Pin catalog endpoint
            $response = Http::withHeaders($this->getHeaders())
                ->get("{$this->config['api_url']}/products");

            if ($response->successful()) {
                return array_map(
                    fn ($p) => $this->normalizeProduct($p),
                    $response->json('products', $response->json() ?? [])
                );
            }

            return [];
        } catch (\Exception $e) {
            Log::error('EZPin fetchCatalog failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    public function fetchCatalogUpdates(\DateTime $since): array
    {
        return $this->fetchCatalog();
    }

    public function checkStock(string $productSku, int $quantity = 1): StockCheckResult
    {
        return new StockCheckResult(available: true, quantity: 999);
    }

    public function cancelOrder(string $providerOrderId): bool
    {
        return false;
    }

    private function getHeaders(): array
    {
        // TODO: Update with actual EZ Pin authentication method
        return [
            'Authorization' => "Bearer {$this->config['api_key']}",
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    private function assertConfigured(): void
    {
        if (! $this->config['api_url'] || ! $this->config['api_key']) {
            throw new \RuntimeException(
                'EZ Pin is not configured. API documentation pending. '
                .'Please update config/services.php with ezpin credentials once received.'
            );
        }
    }

    private function mapStatus(string $status): string
    {
        // TODO: Map actual EZ Pin status codes
        return match (strtolower($status)) {
            'success', 'completed', 'fulfilled' => 'fulfilled',
            'pending', 'processing' => 'pending',
            'cancelled' => 'cancelled',
            default => 'failed',
        };
    }

    private function normalizeProduct(array $raw): array
    {
        return [
            'sku' => $raw['product_code'] ?? $raw['id'],
            'name' => $raw['product_name'] ?? $raw['name'],
            'denomination' => $raw['denomination'] ?? $raw['face_value'] ?? 0,
            'currency' => 'INR',
            'provider' => 'ezpin',
            'raw' => $raw,
        ];
    }
}

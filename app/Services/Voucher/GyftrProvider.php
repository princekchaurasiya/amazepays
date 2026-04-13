<?php

namespace App\Services\Voucher;

use App\Contracts\StockCheckResult;
use App\Contracts\VoucherOrderResult;
use App\Contracts\VoucherProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Gyftrr Voucher Provider
 *
 * NOTE: API documentation not yet received (as of Phase 0).
 * This is a stub that will be completed once Gyftrr API docs arrive.
 *
 * TODO: Once docs received, update:
 * - API base URL
 * - Authentication method
 * - All endpoint paths
 * - Response field mapping
 *
 * ETA: 7-10 days from provider request (submit immediately per Phase 0).
 */
class GyftrProvider implements VoucherProviderInterface
{
    private array $config;

    public function __construct(array $credentials = [])
    {
        $this->config = empty($credentials) ? [
            'api_url' => config('services.gyftrr.api_url'),
            'api_key' => config('services.gyftrr.api_key'),
            'api_secret' => config('services.gyftrr.api_secret'),
        ] : $credentials;
    }

    public function getName(): string
    {
        return 'gyftrr';
    }

    public function supportsIncrementalSync(): bool
    {
        return false;
    }

    public function isAsyncFulfillment(): bool
    {
        return true;
    }

    public function placeOrder(array $orderData): VoucherOrderResult
    {
        $this->assertConfigured();

        try {
            // TODO: Replace with actual Gyftrr API endpoint and payload
            $response = Http::withHeaders($this->getHeaders())
                ->timeout(15)
                ->post("{$this->config['api_url']}/orders", [
                    'brand_id' => $orderData['sku'],
                    'amount' => $orderData['denomination'] ?? $orderData['price'],
                    'quantity' => $orderData['quantity'] ?? 1,
                    'reference' => $orderData['order_id'],
                ]);

            if ($response->successful()) {
                $data = $response->json();

                return new VoucherOrderResult(
                    success: true,
                    status: 'pending',
                    providerOrderId: $data['order_id'] ?? null,
                    raw: $data,
                );
            }

            throw new \RuntimeException('Gyftrr API error: '.$response->body());
        } catch (\Exception $e) {
            Log::error('Gyftrr placeOrder failed', ['error' => $e->getMessage()]);

            return new VoucherOrderResult(success: false, status: 'failed', error: $e->getMessage());
        }
    }

    public function queryOrder(string $providerOrderId): VoucherOrderResult
    {
        $this->assertConfigured();

        try {
            $response = Http::withHeaders($this->getHeaders())
                ->get("{$this->config['api_url']}/orders/{$providerOrderId}");

            if ($response->successful()) {
                $data = $response->json();

                return new VoucherOrderResult(
                    success: true,
                    status: $this->mapStatus($data['status'] ?? ''),
                    providerOrderId: $providerOrderId,
                    voucherCode: $data['voucher_code'] ?? $data['pin_code'] ?? null,
                    expiryDate: $data['expiry_date'] ?? null,
                    raw: $data,
                );
            }

            throw new \RuntimeException('Gyftrr status query failed: '.$response->body());
        } catch (\Exception $e) {
            return new VoucherOrderResult(success: false, status: 'failed', error: $e->getMessage());
        }
    }

    public function fetchCatalog(): array
    {
        $this->assertConfigured();

        try {
            $response = Http::withHeaders($this->getHeaders())
                ->get("{$this->config['api_url']}/brands");

            if ($response->successful()) {
                return array_map(
                    fn ($p) => $this->normalizeProduct($p),
                    $response->json('brands', [])
                );
            }

            return [];
        } catch (\Exception $e) {
            Log::error('Gyftrr fetchCatalog failed', ['error' => $e->getMessage()]);

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
        return [
            'Authorization' => "Bearer {$this->config['api_key']}",
            'Content-Type' => 'application/json',
        ];
    }

    private function assertConfigured(): void
    {
        if (! $this->config['api_url'] || ! $this->config['api_key']) {
            throw new \RuntimeException(
                'Gyftrr is not configured. API documentation pending. '
                .'Update config/services.php with gyftrr credentials once received.'
            );
        }
    }

    private function mapStatus(string $status): string
    {
        return match (strtolower($status)) {
            'success', 'fulfilled', 'completed' => 'fulfilled',
            'pending', 'processing' => 'pending',
            'cancelled' => 'cancelled',
            default => 'failed',
        };
    }

    private function normalizeProduct(array $raw): array
    {
        return [
            'sku' => $raw['brand_id'] ?? $raw['id'],
            'name' => $raw['brand_name'] ?? $raw['name'],
            'denomination' => $raw['denomination'] ?? 0,
            'currency' => 'INR',
            'provider' => 'gyftrr',
            'raw' => $raw,
        ];
    }
}

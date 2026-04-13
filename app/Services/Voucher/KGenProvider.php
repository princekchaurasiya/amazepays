<?php

namespace App\Services\Voucher;

use App\Contracts\StockCheckResult;
use App\Contracts\VoucherOrderResult;
use App\Contracts\VoucherProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KGenProvider implements VoucherProviderInterface
{
    private array $config;

    public function __construct(array $credentials = [])
    {
        $this->config = empty($credentials) ? [
            'api_url' => config('kgen.api_url'),
            'api_key' => config('kgen.api_key'),
            'api_secret' => config('kgen.api_secret'),
        ] : $credentials;
    }

    public function getName(): string
    {
        return 'kgen';
    }

    public function supportsIncrementalSync(): bool
    {
        return false;
    }

    public function isAsyncFulfillment(): bool
    {
        return false; // KGen returns voucher codes synchronously
    }

    public function placeOrder(array $orderData): VoucherOrderResult
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout(30)
                ->post("{$this->config['api_url']}/order", [
                    'productCode' => $orderData['sku'],
                    'qty' => $orderData['quantity'] ?? 1,
                    'denomination' => $orderData['denomination'] ?? $orderData['price'],
                    'orderId' => $orderData['order_id'],
                ]);

            if ($response->successful()) {
                $data = $response->json();

                return new VoucherOrderResult(
                    success: true,
                    status: 'fulfilled',
                    providerOrderId: $data['orderId'] ?? $orderData['order_id'],
                    voucherCode: $data['voucherCode'] ?? $data['code'] ?? null,
                    pin: $data['voucherPin'] ?? $data['pin'] ?? null,
                    expiryDate: $data['expiryDate'] ?? null,
                    raw: $data,
                );
            }

            throw new \RuntimeException('KGen API error: '.$response->body());
        } catch (\Exception $e) {
            Log::error('KGen placeOrder failed', ['error' => $e->getMessage()]);

            return new VoucherOrderResult(success: false, status: 'failed', error: $e->getMessage());
        }
    }

    public function queryOrder(string $providerOrderId): VoucherOrderResult
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->get("{$this->config['api_url']}/order/{$providerOrderId}/status");

            if ($response->successful()) {
                $data = $response->json();

                return new VoucherOrderResult(
                    success: true,
                    status: 'fulfilled',
                    providerOrderId: $providerOrderId,
                    voucherCode: $data['voucherCode'] ?? null,
                    pin: $data['pin'] ?? null,
                    raw: $data,
                );
            }

            return new VoucherOrderResult(success: false, status: 'failed', error: 'Query failed');
        } catch (\Exception $e) {
            return new VoucherOrderResult(success: false, status: 'failed', error: $e->getMessage());
        }
    }

    public function fetchCatalog(): array
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->get("{$this->config['api_url']}/catalog");

            if ($response->successful()) {
                return array_map(
                    fn ($p) => $this->normalizeProduct($p),
                    $response->json('products', [])
                );
            }

            return [];
        } catch (\Exception $e) {
            Log::error('KGen fetchCatalog failed', ['error' => $e->getMessage()]);

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
            'x-api-key' => $this->config['api_key'],
            'Content-Type' => 'application/json',
        ];
    }

    private function normalizeProduct(array $raw): array
    {
        return [
            'sku' => $raw['productCode'] ?? $raw['id'],
            'name' => $raw['productName'] ?? $raw['name'],
            'denomination' => $raw['denomination'] ?? 0,
            'currency' => 'INR',
            'provider' => 'kgen',
            'raw' => $raw,
        ];
    }
}

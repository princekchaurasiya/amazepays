<?php

namespace App\Services\Voucher;

use App\Contracts\StockCheckResult;
use App\Contracts\VoucherOrderResult;
use App\Contracts\VoucherProviderInterface;
use App\Support\ProviderResponseTranslator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WoohooProvider implements VoucherProviderInterface
{
    private array $config;

    public function __construct(array $credentials = [])
    {
        $this->config = empty($credentials) ? [
            'api_url' => config('woohoo.api_url', 'https://woohoo.in/api'),
            'api_key' => config('woohoo.api_key'),
            'api_secret' => config('woohoo.api_secret'),
            'client_id' => config('woohoo.client_id'),
        ] : $credentials;
    }

    public function getName(): string
    {
        return 'woohoo';
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
        try {
            $response = $this->request('POST', '/order/place', [
                'cart' => [
                    [
                        'productId' => $orderData['sku'],
                        'qty' => $orderData['quantity'] ?? 1,
                        'price' => $orderData['price'],
                        'currency' => 'INR',
                    ],
                ],
                'clientOrderId' => $orderData['order_id'],
                'returnUrl' => config('app.url').'/api/v1/webhooks/woohoo',
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return new VoucherOrderResult(
                    success: true,
                    status: 'pending',
                    providerOrderId: $data['orderId'] ?? null,
                    raw: $data,
                );
            }

            $body = $response->json();
            if (is_array($body)) {
                $mapped = ProviderResponseTranslator::fromWoohooPayload($body);
                throw new \RuntimeException($mapped['message']);
            }

            throw new \RuntimeException('Woohoo order API error: '.$response->body());
        } catch (\Exception $e) {
            Log::error('Woohoo placeOrder failed', ['error' => $e->getMessage(), 'order' => $orderData]);

            return new VoucherOrderResult(success: false, status: 'failed', error: $e->getMessage());
        }
    }

    public function queryOrder(string $providerOrderId): VoucherOrderResult
    {
        try {
            $response = $this->request('GET', "/order/{$providerOrderId}/status");

            if ($response->successful()) {
                $data = $response->json();
                $status = $this->mapStatus($data['status'] ?? '');

                $codes = [];
                foreach ($data['items'] ?? [] as $item) {
                    foreach ($item['vouchers'] ?? [] as $voucher) {
                        $codes[] = [
                            'code' => $voucher['code'] ?? null,
                            'pin' => $voucher['pin'] ?? null,
                            'expiry' => $voucher['expiryDate'] ?? null,
                        ];
                    }
                }

                return new VoucherOrderResult(
                    success: true,
                    status: $status,
                    providerOrderId: $providerOrderId,
                    voucherCodes: $codes,
                    voucherCode: $codes[0]['code'] ?? null,
                    pin: $codes[0]['pin'] ?? null,
                    expiryDate: $codes[0]['expiry'] ?? null,
                    raw: $data,
                );
            }

            throw new \RuntimeException('Woohoo status query failed: '.$response->body());
        } catch (\Exception $e) {
            Log::error('Woohoo queryOrder failed', ['error' => $e->getMessage()]);

            return new VoucherOrderResult(success: false, status: 'failed', error: $e->getMessage());
        }
    }

    public function fetchCatalog(): array
    {
        try {
            $response = $this->request('GET', '/catalog/products', ['pageSize' => 1000, 'page' => 1]);
            $products = [];

            if ($response->successful()) {
                foreach ($response->json('products', []) as $product) {
                    $products[] = $this->normalizeProduct($product);
                }
            }

            return $products;
        } catch (\Exception $e) {
            Log::error('Woohoo fetchCatalog failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    public function fetchCatalogUpdates(\DateTime $since): array
    {
        return $this->fetchCatalog();
    }

    public function checkStock(string $productSku, int $quantity = 1): StockCheckResult
    {
        try {
            $response = $this->request('GET', "/catalog/product/{$productSku}/stock");

            if ($response->successful()) {
                $data = $response->json();

                return new StockCheckResult(
                    available: ($data['available'] ?? false) === true,
                    quantity: $data['quantity'] ?? 0,
                );
            }

            return new StockCheckResult(available: false);
        } catch (\Exception $e) {
            return new StockCheckResult(available: false, error: $e->getMessage());
        }
    }

    public function cancelOrder(string $providerOrderId): bool
    {
        return false; // Woohoo does not support order cancellation
    }

    private function request(string $method, string $path, array $data = [])
    {
        $timestamp = now()->timestamp;
        $signature = hash_hmac('sha256', $timestamp.$this->config['api_key'], $this->config['api_secret']);

        return Http::withHeaders([
            'Authorization' => "Bearer {$this->config['api_key']}",
            'X-Timestamp' => $timestamp,
            'X-Signature' => $signature,
        ])->timeout(15)->{strtolower($method)}(
            rtrim($this->config['api_url'], '/').$path,
            $data
        );
    }

    private function mapStatus(string $providerStatus): string
    {
        return match (strtolower($providerStatus)) {
            'success', 'fulfilled', 'completed' => 'fulfilled',
            'pending', 'processing', 'inprogress' => 'pending',
            'cancelled' => 'cancelled',
            default => 'failed',
        };
    }

    private function normalizeProduct(array $raw): array
    {
        return [
            'sku' => $raw['productId'] ?? $raw['id'],
            'name' => $raw['productName'] ?? $raw['name'],
            'denomination' => $raw['denomination'] ?? $raw['price'] ?? 0,
            'currency' => 'INR',
            'type' => $raw['type'] ?? 'gift_card',
            'image_url' => $raw['imageUrl'] ?? null,
            'description' => $raw['description'] ?? null,
            'validity_days' => $raw['validityDays'] ?? null,
            'provider' => 'woohoo',
            'raw' => $raw,
        ];
    }
}

<?php

namespace App\Services\Voucher;

use App\Contracts\StockCheckResult;
use App\Contracts\VoucherOrderResult;
use App\Contracts\VoucherProviderInterface;
use Illuminate\Support\Facades\Log;

/**
 * Value Design / VD Voucher Provider
 * Stub — update with actual API details.
 */
class ValueDesignProvider implements VoucherProviderInterface
{
    private ValueDesignService $service;

    public function __construct(private array $config = [])
    {
        $this->service = new ValueDesignService(empty($config) ? null : $config);
    }

    public function getName(): string
    {
        return 'value_design';
    }

    public function supportsIncrementalSync(): bool
    {
        return false;
    }

    public function isAsyncFulfillment(): bool
    {
        return false;
    }

    public function placeOrder(array $orderData): VoucherOrderResult
    {
        try {
            $token = $this->service->generateToken()['token'];
            $evc = $this->service->getEvc($token, $orderData);
            $raw = $evc['raw'] ?? [];

            return new VoucherOrderResult(
                success: true,
                status: 'pending',
                providerOrderId: (string) ($raw['order_id'] ?? $orderData['order_id'] ?? ''),
                raw: is_array($raw) ? $raw : [],
            );
        } catch (\Throwable $e) {
            Log::error('ValueDesignProvider placeOrder failed', ['error' => $e->getMessage()]);

            return new VoucherOrderResult(success: false, status: 'failed', error: $e->getMessage());
        }
    }

    public function queryOrder(string $providerOrderId): VoucherOrderResult
    {
        try {
            $token = $this->service->generateToken()['token'];
            $status = $this->service->getEvcStatus($token, $providerOrderId, $providerOrderId);

            return new VoucherOrderResult(
                success: true,
                status: 'pending',
                providerOrderId: $providerOrderId,
                raw: $status,
            );
        } catch (\Throwable $e) {
            return new VoucherOrderResult(success: false, status: 'failed', error: $e->getMessage());
        }
    }

    public function fetchCatalog(): array
    {
        return $this->service->fetchCatalog();
    }

    public function fetchCatalogUpdates(\DateTime $since): array
    {
        return [];
    }

    public function checkStock(string $productSku, int $quantity = 1): StockCheckResult
    {
        return new StockCheckResult(available: true, quantity: $quantity);
    }

    public function cancelOrder(string $providerOrderId): bool
    {
        return false;
    }
}

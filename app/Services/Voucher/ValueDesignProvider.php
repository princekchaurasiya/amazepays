<?php

namespace App\Services\Voucher;

use App\Contracts\StockCheckResult;
use App\Contracts\VoucherOrderResult;
use App\Contracts\VoucherProviderInterface;

/**
 * Value Design / VD Voucher Provider
 * Stub — update with actual API details.
 */
class ValueDesignProvider implements VoucherProviderInterface
{
    public function __construct(private array $config = []) {}

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
        return new VoucherOrderResult(success: false, status: 'failed', error: 'Value Design: Not configured.');
    }

    public function queryOrder(string $providerOrderId): VoucherOrderResult
    {
        return new VoucherOrderResult(success: false, status: 'failed', error: 'Not implemented.');
    }

    public function fetchCatalog(): array
    {
        return [];
    }

    public function fetchCatalogUpdates(\DateTime $since): array
    {
        return [];
    }

    public function checkStock(string $productSku, int $quantity = 1): StockCheckResult
    {
        return new StockCheckResult(available: false);
    }

    public function cancelOrder(string $providerOrderId): bool
    {
        return false;
    }
}

<?php

namespace App\Contracts;

interface VoucherProviderInterface
{
    /**
     * Place an order for a voucher with the provider.
     */
    public function placeOrder(array $orderData): VoucherOrderResult;

    /**
     * Query the status of a placed order.
     */
    public function queryOrder(string $providerOrderId): VoucherOrderResult;

    /**
     * Fetch the full product catalog from the provider.
     */
    public function fetchCatalog(): array;

    /**
     * Fetch incremental catalog updates since a given timestamp.
     */
    public function fetchCatalogUpdates(\DateTime $since): array;

    /**
     * Check real-time stock availability for a product.
     */
    public function checkStock(string $productSku, int $quantity = 1): StockCheckResult;

    /**
     * Cancel a pending order (if supported by the provider).
     */
    public function cancelOrder(string $providerOrderId): bool;

    /**
     * Return the provider identifier name.
     */
    public function getName(): string;

    /**
     * Whether the provider supports incremental catalog updates.
     */
    public function supportsIncrementalSync(): bool;

    /**
     * Whether order fulfillment is synchronous or asynchronous.
     */
    public function isAsyncFulfillment(): bool;
}

/**
 * Result value objects for voucher operations.
 */
class VoucherOrderResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $status,         // pending | confirmed | fulfilled | failed | cancelled
        public readonly ?string $providerOrderId = null,
        public readonly ?string $voucherCode = null,
        public readonly ?string $pin = null,
        public readonly ?string $cardNumber = null,
        public readonly ?array $voucherCodes = null,  // for bulk orders
        public readonly ?string $expiryDate = null,
        public readonly ?string $error = null,
        public readonly array $raw = [],
    ) {}

    public function isFulfilled(): bool
    {
        return $this->status === 'fulfilled';
    }

    public function allCodes(): array
    {
        if ($this->voucherCodes) {
            return $this->voucherCodes;
        }

        if ($this->voucherCode) {
            return [['code' => $this->voucherCode, 'pin' => $this->pin]];
        }

        return [];
    }
}

class StockCheckResult
{
    public function __construct(
        public readonly bool $available,
        public readonly int $quantity = 0,
        public readonly ?string $error = null,
    ) {}
}

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

<?php

namespace App\Contracts;

/**
 * Result value object for voucher place-order / status operations.
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

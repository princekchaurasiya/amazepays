<?php

namespace App\Exceptions;

use RuntimeException;

class VoucherFulfillmentException extends RuntimeException
{
    public function __construct(
        public readonly string $provider,
        public readonly ?string $providerError = null,
        string $message = '',
    ) {
        parent::__construct(
            $message ?: "Voucher fulfillment failed via {$provider}: {$providerError}"
        );
    }
}

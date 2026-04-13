<?php

namespace App\Exceptions;

use RuntimeException;

class PaymentFailedException extends RuntimeException
{
    public function __construct(
        public readonly string $gateway,
        public readonly ?string $gatewayError = null,
        string $message = '',
    ) {
        parent::__construct(
            $message ?: "Payment failed via {$gateway}: {$gatewayError}"
        );
    }
}

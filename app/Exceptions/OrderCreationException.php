<?php

namespace App\Exceptions;

use RuntimeException;

class OrderCreationException extends RuntimeException
{
    public function __construct(
        public readonly ?string $reason = null,
        string $message = '',
    ) {
        parent::__construct($message ?: "Order creation failed: {$reason}");
    }
}

<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientBalanceException extends RuntimeException
{
    public function __construct(
        public readonly float $required,
        public readonly float $available,
        string $message = '',
    ) {
        parent::__construct(
            $message ?: "Insufficient balance: required ₹{$required}, available ₹{$available}."
        );
    }
}

<?php

namespace App\Contracts;

class StockCheckResult
{
    public function __construct(
        public readonly bool $available,
        public readonly int $quantity = 0,
        public readonly ?string $error = null,
    ) {}
}

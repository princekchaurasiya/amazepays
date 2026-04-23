<?php

namespace App\Exceptions;

class WalletFrozenException extends \Exception
{
    public function __construct(?string $reason = null)
    {
        parent::__construct($reason ?? 'Wallet is frozen');
    }
}

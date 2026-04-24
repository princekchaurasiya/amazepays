<?php

namespace App\Exceptions\Checkout;

use App\Models\KycThreshold;
use RuntimeException;

final class KycRequiredException extends RuntimeException
{
    /**
     * @param  list<string>  $missingDocumentTypes
     */
    public function __construct(
        public readonly KycThreshold $threshold,
        public readonly array $missingDocumentTypes,
        string $message = 'KYC required to proceed',
    ) {
        parent::__construct($message);
    }
}


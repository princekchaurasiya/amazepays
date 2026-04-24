<?php

namespace App\Services\Voucher;

use App\Services\Voucher\Distributor\ValueDesign\ValueDesignApiClient;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ValueDesignService
{
    public function __construct(private readonly ValueDesignApiClient $client) {}

    public function isConfigured(): bool
    {
        return $this->client->isConfigured();
    }

    public function generateToken(): array
    {
        return $this->client->generateToken();
    }

    public function getBrands(string $token, string $brandCode = ''): array
    {
        return $this->client->getBrands($token, $brandCode);
    }

    public function getStores(string $token, string $brandCode = ''): array
    {
        return $this->client->getStores($token, $brandCode);
    }

    public function getEvc(string $token, array $payload): array
    {
        return $this->client->getEvc($token, $payload);
    }

    public function getEvcStatus(string $token, string $orderId, string $requestRefNo): array
    {
        return $this->client->getEvcStatus($token, $orderId, $requestRefNo);
    }

    public function getActivatedEvc(string $token, string $orderId, string $requestRefNo): array
    {
        return $this->client->getActivatedEvc($token, $orderId, $requestRefNo);
    }

    public function getWalletBalance(string $token): array
    {
        return $this->client->getWalletBalance($token);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchCatalog(): array
    {
        return $this->client->fetchCatalog();
    }
}

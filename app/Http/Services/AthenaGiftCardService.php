<?php

namespace App\Http\Services;

use App\Services\Voucher\Distributor\Lysto\LystoApiClient;
use Illuminate\Support\Facades\Http;

class AthenaGiftCardService
{
    public function __construct(private readonly LystoApiClient $client) {}

    public function listGiftCards($brand = null, $pageNumber = 0, $pageSize = 20)
    {
        return $this->client->listGiftCards(
            $brand !== null ? (string) $brand : null,
            (int) $pageNumber,
            (int) $pageSize,
        );
    }

    public function getSkusByGiftCardId($giftcard_id)
    {
        return $this->client->getSkusByGiftCardId((string) $giftcard_id);
    }

    public function purchaseGiftCard(array $data)
    {
        return $this->client->purchaseGiftCard($data);
    }

    public function getOrderDetails($orderId = null, $merchantOrderRequestId = null)
    {
        return $this->client->getOrderDetails(
            $orderId !== null ? (string) $orderId : null,
            $merchantOrderRequestId !== null ? (string) $merchantOrderRequestId : null,
        );
    }

    public function getWalletBalance()
    {
        return $this->client->getWalletBalance();
    }
}

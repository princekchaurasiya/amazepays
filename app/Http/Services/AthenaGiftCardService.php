<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Http;

class AthenaGiftCardService
{
    protected $baseUrl = 'https://apiv3.lysto.io/api/v1';
    protected $apiKey;
    protected $partnerId;

    public function __construct()
    {
        $this->apiKey = env('LYSTO_API_KEY');
        $this->partnerId = env('LYSTO_PARTNER_ID');
    }

    public function listGiftCards($brand = null, $pageNumber = 0, $pageSize = 20)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'partnerid' => $this->partnerId,
        ])->get("{$this->baseUrl}/giftcards", [
            'brand' => $brand,
            'pageNumber' => $pageNumber,
            'pageSize' => $pageSize,
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        // Handle error
        throw new \Exception("Failed to fetch gift cards: " . $response->body());
    }

    public function getSkusByGiftCardId($giftcard_id)
    {
    $url = "{$this->baseUrl}/giftcards/{$giftcard_id}/skus";

    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $this->apiKey,
        'partnerid' => $this->partnerId,
    ])->get($url);

    if ($response->successful()) {
        return $response->json(); // Includes "status" and "skus" keys
    }

    throw new \Exception("Failed to fetch SKUs: " . $response->body());
    }

    public function purchaseGiftCard(array $data)
    {
        $url = "{$this->baseUrl}/giftcard/purchase";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'partnerid' => $this->partnerId,
            'Content-Type' => 'application/json',
        ])->post($url, $data);

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception("Gift card purchase failed: " . $response->body());
    }

    public function getOrderDetails($orderId)
    {
        $url = "{$this->baseUrl}/orders/{$orderId}";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'partnerid' => $this->partnerId,
        ])->get($url);

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception("Failed to fetch order: " . $response->body());
    }

    public function getWalletBalance()
    {
        $url = "{$this->baseUrl}/wallet-balance";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'partnerid' => $this->partnerId,
        ])->get($url);

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception("Failed to fetch wallet balance: " . $response->body());
    }

}

<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class AthenaGiftCardService
{
    protected $baseUrl = 'https://stagedistapi.lysto.io/api/v1';
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

    protected function decryptGiftCardResponse(array $data, string $secret): string
{
    $ciphertext = hex2bin($data['ciphertext']);
    $iv = hex2bin($data['iv']);
    $tag = hex2bin($data['tag']);

    $key = hash('sha256', $secret, true); // 32-byte key

    $decrypted = openssl_decrypt(
        $ciphertext,
        'aes-256-gcm',
        $key,
        OPENSSL_RAW_DATA,
        $iv,
        $tag
    );

    if ($decrypted === false) {
        throw new \Exception('Failed to decrypt gift codes.');
    }

    return $decrypted;
}

    public function purchaseGiftCard(array $data)
{
    $url = "{$this->baseUrl}/giftcard/purchase";

    // Map payload to expected API body format
    $body = [
        'merchant_order_request_id' => $data['merchant_order_request_id'],
        'giftcard_id' => $data['giftcard_id'], // or use 'giftcard_id' if that's your original key
        'sku_id'      => $data['sku_id'],
        'quantity'    => $data['quantity'],
        'currency'    => $data['currency'],
    ];

    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $this->apiKey,
        'partnerid'     => $this->partnerId,
        'Content-Type'  => 'application/json',
    ])->post($url, $body); // Send body as JSON
   
    $responseData = $response->json();
    $encryptedData = [
        'ciphertext' => $responseData['encryptedgiftcodes'],
        'iv'         => $responseData['iv'],
        'tag'        => $responseData['tag'],
    ];

    $secret = config('services.giftcard.secret'); // or hardcode if needed

    $giftCodes = $this->decryptGiftCardResponse($encryptedData, $secret);

   /*if ($response->successful()) {
        return ("Please find your gift code: " . $giftCodes . " Your order id: " . $responseData['order_id'] );
    }

    throw new \Exception("Gift card purchase failed: " . $response->body());*/
    return [
        'gift_code' => $giftCodes,
        'order_id'  => $responseData['order_id'],
    ];
}


    public function getOrderDetails($orderId = null, $merchantOrderRequestId = null)
{
    $queryParams = [];

    if ($orderId) {
        $queryParams['order_id'] = $orderId;
    }

    if ($merchantOrderRequestId) {
        $queryParams['merchant_order_request_id'] = $merchantOrderRequestId;
    }

    $url = $this->baseUrl . '/orders?' . http_build_query($queryParams);
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

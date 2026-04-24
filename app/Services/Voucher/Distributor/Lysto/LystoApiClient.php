<?php

namespace App\Services\Voucher\Distributor\Lysto;

use Illuminate\Support\Facades\Http;

final class LystoApiClient
{
    /**
     * @return array<string,mixed>
     */
    public function listGiftCards(?string $brand = null, int $pageNumber = 0, int $pageSize = 20): array
    {
        $response = $this->client()->get($this->url('/giftcards'), [
            'brand' => $brand,
            'pageNumber' => $pageNumber,
            'pageSize' => $pageSize,
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Failed to fetch gift cards: '.$response->body());
        }

        return $response->json() ?? [];
    }

    /**
     * @return array<string,mixed>
     */
    public function getSkusByGiftCardId(string $giftCardId): array
    {
        $response = $this->client()->get($this->url("/giftcards/{$giftCardId}/skus"));

        if (! $response->successful()) {
            throw new \RuntimeException('Failed to fetch SKUs: '.$response->body());
        }

        return $response->json() ?? [];
    }

    /**
     * @param  array<string,mixed>  $data
     * @return array{gift_code:string,order_id:mixed}
     */
    public function purchaseGiftCard(array $data): array
    {
        $body = [
            'merchant_order_request_id' => $data['merchant_order_request_id'] ?? null,
            'giftcard_id' => $data['giftcard_id'] ?? null,
            'sku_id' => $data['sku_id'] ?? null,
            'quantity' => $data['quantity'] ?? 1,
            'currency' => $data['currency'] ?? 'INR',
        ];

        $response = $this->client()
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($this->url('/giftcard/purchase'), $body);

        if (! $response->successful()) {
            throw new \RuntimeException('Gift card purchase failed: '.$response->body());
        }

        $responseData = $response->json() ?? [];

        $encryptedData = [
            'ciphertext' => (string) ($responseData['encryptedgiftcodes'] ?? ''),
            'iv' => (string) ($responseData['iv'] ?? ''),
            'tag' => (string) ($responseData['tag'] ?? ''),
        ];

        $giftCodes = $this->decryptGiftCardResponse($encryptedData, $this->secret());

        return [
            'gift_code' => $giftCodes,
            'order_id' => $responseData['order_id'] ?? null,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function getOrderDetails(?string $orderId = null, ?string $merchantOrderRequestId = null): array
    {
        $queryParams = [];
        if ($orderId) {
            $queryParams['order_id'] = $orderId;
        }
        if ($merchantOrderRequestId) {
            $queryParams['merchant_order_request_id'] = $merchantOrderRequestId;
        }

        $response = $this->client()->get($this->url('/orders'), $queryParams);

        if (! $response->successful()) {
            throw new \RuntimeException('Failed to fetch order: '.$response->body());
        }

        $responseData = $response->json() ?? [];

        // Some endpoints may not include encryptedgiftcodes; only attempt decrypt when present.
        if (isset($responseData['encryptedgiftcodes'], $responseData['iv'], $responseData['tag'])) {
            $encryptedData = [
                'ciphertext' => (string) $responseData['encryptedgiftcodes'],
                'iv' => (string) $responseData['iv'],
                'tag' => (string) $responseData['tag'],
            ];
            $responseData['decrypted_giftcodes'] = $this->decryptGiftCardResponse($encryptedData, $this->secret());
        }

        return $responseData;
    }

    /**
     * @return array<string,mixed>
     */
    public function getWalletBalance(): array
    {
        $response = $this->client()->get($this->url('/wallet-balance'));

        if (! $response->successful()) {
            throw new \RuntimeException('Failed to fetch wallet balance: '.$response->body());
        }

        return $response->json() ?? [];
    }

    /**
     * @param  array{ciphertext:string,iv:string,tag:string}  $data
     */
    private function decryptGiftCardResponse(array $data, string $secret): string
    {
        $ciphertext = hex2bin($data['ciphertext']);
        $iv = hex2bin($data['iv']);
        $tag = hex2bin($data['tag']);

        if ($ciphertext === false || $iv === false || $tag === false) {
            throw new \RuntimeException('Invalid encrypted giftcode response.');
        }

        $key = hash('sha256', $secret, true);

        $decrypted = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($decrypted === false) {
            throw new \RuntimeException('Failed to decrypt gift codes.');
        }

        return $decrypted;
    }

    private function url(string $path): string
    {
        return rtrim($this->baseUrl(), '/').'/'.ltrim($path, '/');
    }

    private function baseUrl(): string
    {
        return (string) config('lysto.base_url', '');
    }

    private function secret(): string
    {
        // Upstream uses the API key as the decryption secret in current integration.
        return (string) config('lysto.api_key', '');
    }

    private function client()
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer '.config('lysto.api_key'),
            'partnerid' => config('lysto.partner_id'),
        ])->timeout(30);
    }
}


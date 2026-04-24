<?php

namespace App\Services\Voucher\Distributor\ValueDesign;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ValueDesignApiClient
{
    public function isConfigured(): bool
    {
        return $this->baseUrl() !== ''
            && $this->username() !== ''
            && $this->password() !== ''
            && $this->distributorId() !== '';
    }

    /**
     * @return array{token:string,raw:array<string,mixed>}
     */
    public function generateToken(): array
    {
        $response = $this->postWithBasicHeaders(
            $this->url('api-generatetoken/'),
            ['distributor_id' => $this->distributorId()]
        );

        $json = $response->json() ?? [];
        $tokenRaw = (string) ($json['token'] ?? '');
        $token = $this->decryptToJsonStringOrRaw($tokenRaw);

        if ($token === '') {
            throw new \RuntimeException('Value Design token response missing token value.');
        }

        return [
            'token' => $token,
            'raw' => $json,
        ];
    }

    /**
     * @return array<int, array<string,mixed>>
     */
    public function getBrands(string $token, string $brandCode = ''): array
    {
        $response = $this->postWithToken(
            $this->url('api-getbrand/'),
            $token,
            ['BrandCode' => $brandCode]
        );

        return $this->decodeEncryptedDataResponse($response);
    }

    /**
     * @return array<int, array<string,mixed>>
     */
    public function getStores(string $token, string $brandCode = ''): array
    {
        $response = $this->postWithToken(
            $this->url('api-getstore/'),
            $token,
            ['BrandCode' => $brandCode]
        );

        return $this->decodeEncryptedDataResponse($response);
    }

    /**
     * @param  array<string,mixed>  $payload
     * @return array{raw:array<string,mixed>,decoded_data:mixed}
     */
    public function getEvc(string $token, array $payload): array
    {
        $encryptedPayload = $this->encryptPayload(json_encode($payload, JSON_THROW_ON_ERROR));
        $response = $this->postWithToken(
            $this->url('getevc/'),
            $token,
            ['payload' => $encryptedPayload]
        );

        $json = $response->json() ?? [];
        $decodedData = null;
        if (! empty($json['data']) && is_string($json['data'])) {
            $decodedData = $this->decodeEncryptedStringAsJson($json['data']);
        }

        return [
            'raw' => $json,
            'decoded_data' => $decodedData,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function getEvcStatus(string $token, string $orderId, string $requestRefNo): array
    {
        $response = $this->postWithToken(
            $this->url('getevcstatus/'),
            $token,
            [
                'order_id' => $orderId,
                'request_ref_no' => $requestRefNo,
            ]
        );

        return $response->json() ?? [];
    }

    /**
     * @return array{raw:array<string,mixed>,decoded_data:mixed}
     */
    public function getActivatedEvc(string $token, string $orderId, string $requestRefNo): array
    {
        $response = $this->postWithToken(
            $this->url('getactivatedevc/'),
            $token,
            [
                'order_id' => $orderId,
                'request_ref_no' => $requestRefNo,
            ]
        );

        $json = $response->json() ?? [];
        $decodedData = null;
        if (! empty($json['data']) && is_string($json['data'])) {
            $decodedData = $this->decodeEncryptedStringAsJson($json['data']);
        }

        return [
            'raw' => $json,
            'decoded_data' => $decodedData,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function getWalletBalance(string $token): array
    {
        $response = $this->postWithToken(
            $this->url('getwalletbalance/'),
            $token,
            ['distributor_id' => $this->distributorId()]
        );

        return $response->json() ?? [];
    }

    /**
     * @return array<int, array<string,mixed>>
     */
    public function fetchCatalog(): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        try {
            $token = $this->generateToken()['token'];
            $brands = $this->getBrands($token);
            $items = [];

            foreach ($brands as $brand) {
                if (! is_array($brand)) {
                    continue;
                }
                $sku = (string) ($brand['BrandCode'] ?? '');
                if ($sku === '') {
                    continue;
                }

                $imageUrl = null;
                $rawImages = $brand['Images'] ?? null;
                if (is_string($rawImages) && $rawImages !== '') {
                    $decoded = json_decode(str_replace("'", '"', $rawImages), true);
                    if (is_array($decoded)) {
                        $imageUrl = $decoded[0] ?? null;
                    }
                }

                $items[] = [
                    'sku' => $sku,
                    'name' => (string) ($brand['BrandName'] ?? $sku),
                    'description' => $brand['Description'] ?? null,
                    'tnc' => $brand['TnC'] ?? null,
                    'currency' => 'INR',
                    'price' => [
                        'type' => strtoupper((string) ($brand['Brandtype'] ?? '')) === 'RANGE' ? 'RANGE' : 'SLAB',
                        'min' => isset($brand['minPrice']) ? (float) $brand['minPrice'] : null,
                        'max' => isset($brand['maxPrice']) ? (float) $brand['maxPrice'] : null,
                        'denominations' => $this->parseDenominations((string) ($brand['DenominationList'] ?? '')),
                    ],
                    'image_url' => $imageUrl,
                    'provider' => 'value_design',
                    'raw' => $brand,
                ];
            }

            return $items;
        } catch (\Throwable $e) {
            Log::error('ValueDesignApiClient::fetchCatalog failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    private function url(string $path): string
    {
        return rtrim($this->baseUrl(), '/').'/'.ltrim($path, '/');
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    private function postWithBasicHeaders(string $url, array $payload): Response
    {
        $response = Http::timeout(30)
            ->acceptJson()
            ->withHeaders([
                'username' => $this->username(),
                'password' => $this->password(),
            ])
            ->post($url, $payload);

        if (! $response->successful()) {
            throw new \RuntimeException("Value Design request failed ({$response->status()}): ".$response->body());
        }

        return $response;
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    private function postWithToken(string $url, string $token, array $payload): Response
    {
        $response = Http::timeout(30)
            ->acceptJson()
            ->withHeaders(['token' => $token])
            ->post($url, $payload);

        if (! $response->successful()) {
            throw new \RuntimeException("Value Design request failed ({$response->status()}): ".$response->body());
        }

        return $response;
    }

    /**
     * @return array<int, array<string,mixed>>
     */
    private function decodeEncryptedDataResponse(Response $response): array
    {
        $json = $response->json() ?? [];
        $encrypted = (string) ($json['data'] ?? '');
        if ($encrypted === '') {
            return [];
        }

        $decoded = $this->decodeEncryptedStringAsJson($encrypted);

        return is_array($decoded) ? $decoded : [];
    }

    private function decodeEncryptedStringAsJson(string $encrypted): mixed
    {
        $plaintext = $this->decryptToJsonStringOrRaw($encrypted);
        $decoded = json_decode($plaintext, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
    }

    private function decryptToJsonStringOrRaw(string $encrypted): string
    {
        $normalized = str_replace(' ', '+', urldecode($encrypted));
        $ciphertext = base64_decode($normalized, true);
        if ($ciphertext === false || $ciphertext === '') {
            return $encrypted;
        }

        $decrypted = openssl_decrypt(
            $ciphertext,
            'AES-256-CBC',
            $this->secretKey(),
            OPENSSL_RAW_DATA,
            $this->iv()
        );

        return ($decrypted !== false && $decrypted !== '') ? $decrypted : $encrypted;
    }

    private function encryptPayload(string $payload): string
    {
        $encrypted = openssl_encrypt(
            $payload,
            'AES-256-CBC',
            $this->secretKey(),
            OPENSSL_RAW_DATA,
            $this->iv()
        );

        if ($encrypted === false) {
            throw new \RuntimeException('Value Design payload encryption failed.');
        }

        return base64_encode($encrypted);
    }

    /**
     * @return array<int, float>
     */
    private function parseDenominations(string $list): array
    {
        if (trim($list) === '') {
            return [];
        }

        $parts = preg_split('/[,\|]/', $list) ?: [];
        $values = [];
        foreach ($parts as $part) {
            $val = trim($part);
            if ($val !== '' && is_numeric($val)) {
                $values[] = (float) $val;
            }
        }

        return $values;
    }

    private function baseUrl(): string
    {
        return (string) config('valuedesign.base_url', '');
    }

    private function username(): string
    {
        return (string) config('valuedesign.username', '');
    }

    private function password(): string
    {
        return (string) config('valuedesign.password', '');
    }

    private function distributorId(): string
    {
        return (string) config('valuedesign.distributor_id', '');
    }

    private function secretKey(): string
    {
        return (string) config('valuedesign.secret_key', '');
    }

    private function iv(): string
    {
        return (string) config('valuedesign.iv', '');
    }
}


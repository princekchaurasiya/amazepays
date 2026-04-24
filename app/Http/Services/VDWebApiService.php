<?php

namespace App\Http\Services;

use App\Models\Brand;
use App\Models\StoreDetail;
use App\Services\Voucher\Distributor\ValueDesign\ValueDesignApiClient;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VDWebApiService
{
    public function __construct(private readonly ValueDesignApiClient $client) {}

    public function getToken()
    {
        try {
            return $this->client->generateToken()['token'];
        } catch (RequestException $e) {
            // Check for cURL error 55
            if ($e->getHandlerContext()['errno'] === 55) {
                throw new \Exception('Please try after sometime');
            }

            throw $e; // rethrow if it's another error
        }
    }

    public function getEncryptedPayload($token)
    {
        try {
            return $this->client->getBrands($token);
        } catch (\Throwable $e) {
            \Log::error('Failed to fetch encrypted payload', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * New: Normalize an encrypted token from URL/body by url-decoding and fixing spaces.
     * Does not change existing decrypt logic; can be used by callers before decryptAES().
     */
    public function normalizeEncryptedInput(?string $encrypted): string
    {
        if ($encrypted === null) {
            return '';
        }
        $normalized = urldecode($encrypted);
        // Some gateways convert '+' to space inside URLs; revert for base64
        $normalized = str_replace(' ', '+', $normalized);

        return $normalized;
    }

    /**
     * New: Tolerant decryption that tries base64 (with url-fix) and hex as fallback.
     * Uses the same env variables and cipher as existing code, but does not modify it.
     */
    public function tryDecryptEvc(string $encrypted): array
    {
        $result = [
            'ok' => false,
            'decrypted' => '',
            'mode' => null,
            'error' => null,
        ];

        if ($encrypted === '') {
            $result['error'] = 'empty_input';
            \Log::error('tryDecryptEvc: empty encrypted input');

            return $result;
        }

        $key = config('valuedesign.secret_key');
        $iv = config('valuedesign.iv');

        // Attempt 1: base64 with URL normalization
        $prepared = $this->normalizeEncryptedInput($encrypted);
        $ciphertext = base64_decode($prepared, true);
        if ($ciphertext !== false && $ciphertext !== '') {
            $plain = openssl_decrypt($ciphertext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
            if ($plain !== false && $plain !== '') {
                $result['ok'] = true;
                $result['decrypted'] = $plain;
                $result['mode'] = 'base64_urlfix';

                return $result;
            }
        }

        // Attempt 2: hex fallback
        if (ctype_xdigit($prepared) && (strlen($prepared) % 2 === 0)) {
            $cipherHex = @hex2bin($prepared);
            if ($cipherHex !== false && $cipherHex !== '') {
                $plain = openssl_decrypt($cipherHex, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
                if ($plain !== false && $plain !== '') {
                    $result['ok'] = true;
                    $result['decrypted'] = $plain;
                    $result['mode'] = 'hex';

                    return $result;
                }
            }
        }

        $result['error'] = 'decrypt_failed';
        \Log::error('tryDecryptEvc: decryption failed', [
            'input_len' => strlen($encrypted),
            'prepared_prefix' => substr($prepared, 0, 16),
            'key_len' => $key !== null ? strlen($key) : null,
            'iv_len' => $iv !== null ? strlen($iv) : null,
        ]);

        return $result;
    }

    public function decryptAES(string $encryptedBase64): string
    {
        // Legacy API; keep method surface but route through the canonical client decrypt path
        return $encryptedBase64;
    }

    public function getBrands(string $token, string $brandCode = '')
    {
        return $this->client->getBrands($token, $brandCode);
    }

    public function storeBrandsFromResponse(array $brandsResponse)
    {
        // Extract JSON string from response
        $jsonString = $brandsResponse['brands']['original']['decrypted_data'] ?? null;

        if (! $jsonString) {
            throw new \Exception('No decrypted data found.');
        }

        // Decode JSON string to array
        $brandsArray = json_decode($jsonString, true);
        if (! is_array($brandsArray)) {
            throw new \Exception('Invalid JSON data for brands.');
        }

        foreach ($brandsArray as $brandData) {
            Brand::updateOrCreate(
                ['brand_code' => $brandData['BrandCode']],
                [
                    'brand_name' => $brandData['BrandName'] ?? null,
                    'brand_type' => $brandData['Brandtype'] ?? null,
                    'discount' => $brandData['Discount'] ?? null,
                    'min_price' => $brandData['minPrice'] ?? null,
                    'max_price' => $brandData['maxPrice'] ?? null,
                    'denomination_list' => $brandData['DenominationList'] ?? null,
                    'stock_available' => $brandData['StockAvailable'] ?? null,
                    'category' => $brandData['Category'] ?? null,
                    'description' => $brandData['Description'] ?? null,
                    'images' => json_decode($brandData['Images'], true) ?: null,
                    'tnc' => $brandData['TnC'] ?? null,
                    'important_instruction' => $brandData['ImportantInstruction'] ?? null,
                    'redeem_steps' => $brandData['RedeemSteps'] ?? null,
                ]
            );
        }
    }

    public function getStores(string $token, string $brandCode)
    {
        return $this->client->getStores($token, $brandCode);
    }

    public function syncStoresToDatabase(string $token, string $brandCode): bool
    {
        $stores = $this->getStores($token, $brandCode);

        if (! $stores) {
            return false;
        }

        foreach ($stores as $store) {
            $hasStoreCode = isset($store['StoreCode']) && $store['StoreCode'] !== null && $store['StoreCode'] !== '';

            $where = $hasStoreCode
                ? ['store_code' => $store['StoreCode']]
                : [
                    'brand_code' => $store['BrandCode'] ?? '',
                    'address' => $store['Address'] ?? '',
                    'city' => $store['City'] ?? '',
                    'state' => $store['State'] ?? '',
                    'contact_number' => $store['ContactNumber'] ?? null,
                ];

            StoreDetail::updateOrCreate(
                $where,
                [
                    'brand_code' => $store['BrandCode'] ?? '',
                    'brand_name' => $store['BrandName'] ?? '',
                    'address' => $store['Address'] ?? '',
                    'city' => $store['City'] ?? '',
                    'state' => $store['State'] ?? '',
                    'country' => $store['Country'] ?? '',
                    'contact_number' => $store['ContactNumber'] ?? null,
                ]
            );
        }

        return true;
    }

    public function displayBrands(string $token, string $brandCode = '')
    {
        // Legacy Blade flow removed. Use app/Services/Voucher/Distributor/ValueDesign client
        // via controllers under /panel/value-design.
        return null;
    }

    public function getEvc(string $token, string $payload)
    {
        return $this->client->getEvc($token, json_decode($payload, true) ?: [])['raw'];
    }

    public function getEvcStatus(string $token, string $orderId, string $requestRefNo)
    {
        return $this->client->getEvcStatus($token, $orderId, $requestRefNo);
    }

    public function getActivatedEvc($token, $orderId, $requestRefNo)
    {
        $url = 'https://at.valuedesign.co.in/distributor/getactivatedevc/';

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->post($url, [
            'order_id' => $orderId,
            'request_ref_no' => $requestRefNo,
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }
}

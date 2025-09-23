<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\StoreDetail;
use App\Http\Controllers\VDWebController;
use App\Models\Brand;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;

class VDWebApiService
{
    protected $baseUrl = 'https://at.valuedesign.co.in/distributor/';

    protected $username;
    protected $password;
    protected $distributorId;

    public function __construct()
    {
        $this->username = config('services.vdweb.username');
        $this->password = config('services.vdweb.password');
        $this->distributorId = config('services.vdweb.distributor_id');
    }

    public function getToken()
    {
        $url = env('TOKEN_API_URL');
        $distributorId = env('DISTRIBUTOR_ID');

        try
        {
        $response = Http::timeout(20) // 20 seconds
    ->withHeaders([
        'username' => env('API_USERNAME'),
        'password' => env('API_PASSWORD'),
    ])->post($url, [
            'distributor_id' => $distributorId,
        ]);
    }
    catch (RequestException $e) {
        // Check for cURL error 55
        if ($e->getHandlerContext()['errno'] === 55) {
            throw new \Exception("Please try after sometime");
        }

        throw $e; // rethrow if it's another error
    }

        if ($response->successful()) {
            $encryptedToken = $response->json('token');

            try {
                $decryptedToken = $this->decryptAES($encryptedToken);
            } catch (\Exception $e) {
                $decryptedToken = 'Decryption failed: ' . $e->getMessage();
            }

            return $decryptedToken;
        } else {
            return response()->json(['error' => 'Token request failed', 'details' => $response->body()], 500);
        }
    }
    public function getEncryptedPayload($token)
{
    $response = Http::withHeaders([
        'token' => $token,
    ])->post($this->baseUrl . 'api-getbrand/', [  // or your actual encrypted payload endpoint
        'BrandCode' => ''
    ]);

    if ($response->successful()) {
        return $response->body(); // assuming response is encrypted string
    }

    \Log::error('Failed to fetch encrypted payload', ['body' => $response->body()]);
    return null;
}

	/**
	 * Decode env-provided key/iv values which may be raw, base64, or hex.
	 */
	private function decodeKeyOrIv(?string $value): ?string
	{
		if ($value === null || $value === '') {
			return null;
		}
		$value = trim($value);
		// Try base64 (strict)
		$decodedB64 = base64_decode($value, true);
		if ($decodedB64 !== false && $decodedB64 !== '') {
			// Heuristic: if re-encoding yields the same (ignoring padding differences), accept
			if (rtrim($value, '=') === rtrim(base64_encode($decodedB64), '=')) {
				return $decodedB64;
			}
		}
		// Try hex
		if (ctype_xdigit($value) && (strlen($value) % 2 === 0)) {
			$decodedHex = @hex2bin($value);
			if ($decodedHex !== false) {
				return $decodedHex;
			}
		}
		// Fallback raw
		return $value;
	}

	/**
	 * Load and normalize AES key and IV from environment.
	 */
	private function loadAesKeyAndIv(): array
	{
		// Support multiple env names for compatibility
		$keyRaw = env('AES_SECRET_KEY') ?: env('AES_KEY');
		$ivRaw = env('AES_IV');

		$key = $this->decodeKeyOrIv($keyRaw);
		$iv = $this->decodeKeyOrIv($ivRaw);

		// Log lengths only (not values)
		Log::debug('AES materials loaded', [
			'key_len' => $key !== null ? strlen($key) : null,
			'iv_len' => $iv !== null ? strlen($iv) : null,
		]);

		return [$key, $iv];
	}

	/**
	 * Robust AES-256-CBC decryption supporting url/base64/hex inputs.
	 */
	public function decryptAES(string $encryptedInput): string
    {
		[$key, $iv] = $this->loadAesKeyAndIv();

		if (empty($encryptedInput)) {
			Log::error('decryptAES: input is empty');
			return '';
		}

		$original = $encryptedInput;
		$prepared = urldecode($encryptedInput);
		$prepared = str_replace(' ', '+', $prepared);

		$ciphertext = base64_decode($prepared, true);
		if ($ciphertext === false) {
			// Try hex as fallback
			if (ctype_xdigit($prepared) && (strlen($prepared) % 2 === 0)) {
				$ciphertext = @hex2bin($prepared);
			}
		}

		if ($ciphertext === false || $ciphertext === '' || $ciphertext === null) {
			Log::error('decryptAES: ciphertext decode failed', [
				'input_len' => strlen($original),
				'prepared_prefix' => substr($prepared, 0, 16),
			]);
			return '';
		}

		$decrypted = @openssl_decrypt(
			$ciphertext,
			'AES-256-CBC',
			$key,
			OPENSSL_RAW_DATA,
			$iv
		);

		if ($decrypted === false || $decrypted === '') {
			Log::error('decryptAES: openssl_decrypt failed or empty', [
				'cipher_len' => strlen($ciphertext),
				'key_len' => $key !== null ? strlen($key) : null,
				'iv_len' => $iv !== null ? strlen($iv) : null,
				'openssl_err' => openssl_error_string(),
			]);
			return '';
		}

		return $decrypted;
    }

    public function getBrands(string $token, string $brandCode = '')
    {
        try {
            $response = Http::withHeaders([
                'token' => $token,
            ])->post($this->baseUrl . 'api-getbrand/', [
                'BrandCode' => $brandCode,
            ]);

            if ($response->successful()) {
                $brands = $response->json();
                $encryptedBrandData = $brands['data'] ?? null;

                if (!$encryptedBrandData) {
                    Log::error('Get brands: missing data field', ['response' => $brands]);
                    return null;
                }

                $decryptedBrandData = $this->decryptAES($encryptedBrandData);
                $decoded = json_decode($decryptedBrandData, true);

                if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                    Log::error('Get brands: JSON decode failed', ['error' => json_last_error_msg(), 'raw' => $decryptedBrandData]);
                    return null;
                }

                return $decoded;
            }

            Log::error('Get brands failed', ['status' => $response->status(), 'body' => $response->body()]);
            return null;
        } catch (\Exception $e) {
            Log::error('Get brands exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function storeBrandsFromResponse(array $brandsResponse)
{
    // Extract JSON string from response
    $jsonString = $brandsResponse['brands']['original']['decrypted_data'] ?? null;

    if (!$jsonString) {
        throw new \Exception('No decrypted data found.');
    }

    // Decode JSON string to array
    $brandsArray = json_decode($jsonString, true);
    dd($brandsArray);

    if (!is_array($brandsArray)) {
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
    try {
        $response = Http::withHeaders([
            'token' => $token,
        ])->post($this->baseUrl . 'api-getstore/', [
            'BrandCode' => $brandCode,
        ]);

        if ($response->successful()) {
            $data = $response->json();

            \Log::info('Store API response', $data);

            $encodedData = $data['data'] ?? '';

            $decrypted = $this->decryptAES($encodedData);

            if (!$decrypted) {
                \Log::error('AES decryption failed');
                return [];
            }

            \Log::debug('Decrypted store data:', ['decrypted' => $decrypted]);
            $stores = json_decode($decrypted, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                \Log::error('JSON decode error: ' . json_last_error_msg());
                return [];
            }

            return $stores;
        }
    } 
    catch (\Exception $e) {
        if (str_contains($e->getMessage(), 'cURL error 55')) {
            return response()->json(['message' => 'Please try after sometime'], 503);
        }
    }
        \Log::error('Failed to fetch stores', ['body' => $response->body()]);
        return null;
    }

    public function syncStoresToDatabase(string $token, string $brandCode): bool
{
    $stores = $this->getStores($token, $brandCode);

    if (!$stores) {
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
        try {
            $response = Http::withHeaders([
                'token' => $token,
            ])->post($this->baseUrl . 'api-getbrand/', [
                'BrandCode' => $brandCode, // leave empty string for all brands
            ]);

            if ($response->successful()) {
                $brands = $response->json();

                $encryptedBrandData = $brands['data'] ?? null;

                if ($encryptedBrandData) {
                    $decryptedBrandData = $this->decryptAES($encryptedBrandData);
                    
                } else {
                    $decryptedBrandData = 'No data field in response.';
                }

                return view('brands.index', [
                    'brands' => json_decode($decryptedBrandData, true)
                    ]);
                //return $response->json(); // Will return array of brands or brand details
            }

            Log::error('Get brands failed', ['response' => $response->body()]);
            return null;
        } catch (\Exception $e) {
            Log::error('Get brands exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

public function getEvc(string $token, string $payload)
    {
		[$key, $iv] = $this->loadAesKeyAndIv();

       //$encryptedPayload = \App\Helpers\AesHelper::encrypt($payload);
		$rawEncrypted = openssl_encrypt(
        $payload,
        'AES-256-CBC',
        $key,
        OPENSSL_RAW_DATA,
        $iv
        );

        $encryptedPayload = base64_encode($rawEncrypted);

        $payload = [
                    'payload' => $encryptedPayload, // encryptedPayload is the full string you showed
                    ];
		Log::info('Payload Sent:', [
			'payload_len' => strlen($encryptedPayload),
		]);

        $response = Http::withHeaders([
            'token' => $token,
             'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post('https://at.valuedesign.co.in/distributor/getevc/', $payload);

        // Debug response
        if ($response->failed()) {
            Log::error('API Error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
                }
        
		Log::debug('Final Request', [
			'headers' => [
				'token_present' => !empty($token),
			],
			'body_keys' => array_keys($payload),
		]);
        return $response->json();
    }

public function getEvcStatus(string $token, string $orderId, string $requestRefNo)
{
    $response = Http::withHeaders([
        'token' => $token,
    ])->post($this->baseUrl . 'getevcstatus/', [
        'order_id' => $orderId,
        'request_ref_no' => $requestRefNo,
    ]);

    return $response->successful() ? $response->json() : null;
}

public function getActivatedEvc($token, $orderId, $requestRefNo)
{
    $url = 'https://at.valuedesign.co.in/distributor/getactivatedevc/';

    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $token,
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

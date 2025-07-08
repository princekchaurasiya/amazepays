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
    protected $baseUrl = 'http://cards.vdwebapi.com/distributor/';

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

 public function decryptAES(string $encryptedBase64): string
    {
        $key = env('AES_SECRET_KEY');
        $iv = env('AES_IV');

        $ciphertext = base64_decode($encryptedBase64, true);
        if ($ciphertext === false) {
            return 'Base64 decode failed';
        }

        $decrypted = openssl_decrypt(
            $ciphertext,
            'AES-256-CBC',
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        return $decrypted ?: 'Decryption failed';
    }

    public function getBrands(string $token, string $brandCode = '')
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

                return response()->json([
                    'decrypted_data' => $decryptedBrandData,
                ]);
                //return $response->json(); // Will return array of brands or brand details
            }

            $this->storeBrandsFromResponse(json_decode($decryptedBrandData, true));

            Log::error('Get brands failed', ['response' => $response->body()]);
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
    StoreDetail::updateOrCreate(
        ['store_code' => $store['StoreCode'] ?? null], // or unique identifier
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
        $key = env('AES_KEY');
        $iv = env('AES_IV');

       /*$encryptedPayload = \App\Helpers\AesHelper::encrypt($payload, $key, $iv);
       $jsonPayload = json_encode($payload);
       dd($jsonPayload);
        $response = Http::withHeaders([
            'token' => $token,
             'Content-Type' => 'application/json', // or text/plain if required by API
])->withBody($payload, 'application/json')
       // ->post($this->baseUrl . 'getevc/', $encryptedPayload);
       ->post('http://cards.vdwebapi.com/distributor/getevc');*/
        $payload="bgtjBywIFw/PSlbjRtg9bsAuqEkdfzQIQIire2c8axrlgrjBFS/oRY+9v5Np6yeHu5JQo71NUMEqYXM0jbfgadfZyt6sJWrM40EnvwnJwhaQD1eifDTY+ekswcoCW70dHjtFxzEQVeiVhgENRgEOenihzC6TE6pF1T7hjeOzhF/tJDP8kGNzHHNyKKof5dtKmmkELKTPr4ugRuj78q1osZQh559nxlI2D4nVL/hnhm7DuGv5s4qy26SUlP1IwWc/9epIU3e3HBfQP1lYYK1b4auueO9L2o3nRsJTZxmAEtNeMP8JadyL+7Np12XQ5T59qFfom8RrC9b87iHkvif1AXdnDODe25qh/r22Zg59z105FeJB/wmwywVHbWna9SjjmTZ5vCUvHs2IYoKUTaztd7bbyx+yxAjbT+AGxtchx/He/VjX+Do5mzx2r+rbjKK8bmH4/16gsD3BNBtJWKfAIc3r49xp4k3uuorCvTF52P562Y32LXZw+cwXU2+IWqjMGRB/BdUtsicKOHZ86c0Ky4DzDID7xtnFZ1wYDhpMZ0c=";
       $response = Http::withHeaders([
    'token' => $token,
    'Content-Type' => 'text/plain',
])->withBody($payload, 'text/plain')
  ->post('http://cards.vdwebapi.com/distributor/getevc');
        
       dd($response);
        return $response->successful() ? $response->json() : null;
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
}

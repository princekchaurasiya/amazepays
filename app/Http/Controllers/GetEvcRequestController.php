<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\GetEvcRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Http\Services\VDWebApiService;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class GetEvcRequestController extends Controller
{
    protected $baseUrl = 'https://at.valuedesign.co.in/distributor/';
    private VDWebApiService $vdWeb;

    public function __construct(VDWebApiService $vdWeb)
    {
        $this->vdWeb = $vdWeb;
    }

    public function create(): Response
    {
        if (request()->has('denomination') && request()->has('quantity')) {
            $early = $this->storeGiftCardDataResponse();
            if ($early instanceof Response) {
                return $early;
            }
        }

        $denom = request('denomination');
        $qty = request('quantity');
        $payable = '';
        if ($denom !== null && $denom !== '' && $qty !== null && $qty !== '') {
            $payable = (string) round((float) $denom * (int) $qty, 2);
        }

        return Inertia::render('Storefront/EvcRequestCreate', [
            'prefill' => [
                'denomination' => request('denomination'),
                'quantity' => request('quantity'),
                'vd_discount' => request('vd_discount'),
                'vd_brand_code' => request('vd_brand_code'),
                'gift_send_option' => request('gift_send_option'),
                'payable_amount' => request('payable_amount') ?: $payable,
            ],
        ]);
    }

    private function storeGiftCardDataResponse(): ?Response
    {
        try {
            // Create a new record with the gift card data
            $token = $this->vdWeb->getToken();
            $requestedBrandCode = request('vd_brand_code', '');
            $brandResponse = $token ? $this->getBrands($token, (string) $requestedBrandCode) : null;

            $derivedSkuCode = (string) $requestedBrandCode;
            if (is_array($brandResponse)) {
                if (isset($brandResponse['SkuCode'])) {
                    $derivedSkuCode = (string) $brandResponse['SkuCode'];
                } elseif (isset($brandResponse['sku_code'])) {
                    $derivedSkuCode = (string) $brandResponse['sku_code'];
                } elseif (isset($brandResponse['BrandCode'])) {
                    $derivedSkuCode = (string) $brandResponse['BrandCode'];
                } elseif (isset($brandResponse[0])) {
                    $first = $brandResponse[0];
                    if (is_array($first)) {
                        $derivedSkuCode = (string) ($first['SkuCode'] ?? $first['sku_code'] ?? $first['BrandCode'] ?? $derivedSkuCode);
                    }
                }
            }

            $giftCardData = [
                'no_of_card' => (int) request('quantity', 1),
                'amount' => (float) request('denomination', 0),
                'firstname' => request('firstname'),
                'lastname' => request('lastname'),
                'email' => request('email'),
                'mobile_no' => request('mobile_no'),
                'address' => request('address'),
                'city' => request('city'),
                'state' => request('state'),
                'country' => request('country'),
                'pincode' => request('pincode'),
                'curr' => request('curr'),
                'order_id' => Str::upper(Str::random(20)),
                'receipt_no' => Str::upper(Str::random(12)),
                'req_id' => Str::upper(Str::random(16)),
                'distributor_id' => "VDAmazepay97",
                'sku_code' => $derivedSkuCode,
                // Additional gift card specific fields
                'gift_send_option' => request('gift_send_option'),
                'delivery_mode' => request('delivery_mode'),
                'receiver_name' => request('receiver_name'),
                'receiver_email' => request('receiver_email'),
                'receiver_mobile' => request('receiver_mobile'),
                'receiver_msg' => request('receiver_msg'),
                'vd_discount' => request('vd_discount'),
                'vd_brand_code' => request('vd_brand_code'),
            ];

            $evcRequest = GetEvcRequest::create($giftCardData);

            return Inertia::render('Checkout/Status', [
                'status' => 'success',
                'msg' => 'Gift card request saved. Reference: '.$evcRequest->req_id,
                'amount' => $evcRequest->amount,
            ]);
        } catch (\Exception $e) {
            Log::error('Error storing gift card data: '.$e->getMessage());
        }

        return null;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'no_of_card' => 'required|integer|min:1',
            'amount' => 'required|numeric',
            'firstname' => 'required|string',
            'lastname' => 'required|string',
            'email' => 'required|email',
            'mobile_no' => 'required|string',
            'address' => 'required|string',
            'city' => 'required|string',
            'state' => 'required|string',
            'country' => 'required|string',
            'pincode' => 'required|string',
            'curr' => 'required|string',
        ]);

        // Generate IDs automatically
        $validated['order_id'] = Str::upper(Str::random(20)); // Example: PQL98PQ9IUISPQQID74
        $validated['receipt_no'] = Str::upper(Str::random(12)); // Example: V9IQUZAOJLIY
        $validated['req_id'] = Str::upper(Str::random(16)); // Example: 1LLZAOJU92YTkk55
        $validated['distributor_id'] = "VDAmazepay97";
        $token = $this->vdWeb->getToken();
        $requestedBrandCode = (string) ($validated['vd_brand_code'] ?? request('vd_brand_code', ''));
        $brandResponse = $token ? $this->getBrands($token, $requestedBrandCode) : null;

        $derivedSkuCode = (string) $requestedBrandCode;
        if (is_array($brandResponse)) {
            if (isset($brandResponse['SkuCode'])) {
                $derivedSkuCode = (string) $brandResponse['SkuCode'];
            } elseif (isset($brandResponse['sku_code'])) {
                $derivedSkuCode = (string) $brandResponse['sku_code'];
            } elseif (isset($brandResponse['BrandCode'])) {
                $derivedSkuCode = (string) $brandResponse['BrandCode'];
            } elseif (isset($brandResponse[0])) {
                $first = $brandResponse[0];
                if (is_array($first)) {
                    $derivedSkuCode = (string) ($first['SkuCode'] ?? $first['sku_code'] ?? $first['BrandCode'] ?? $derivedSkuCode);
                }
            }
        }

        $validated['sku_code'] = $derivedSkuCode;
        GetEvcRequest::create($validated);

        return redirect()->back()->with('success', 'Request saved successfully!');
    }

    public function show($orderId, $requestRefNo): Response
    {
        $evcRequest = GetEvcRequest::where('order_id', $orderId)
            ->where('req_id', $requestRefNo)
            ->first();

        return Inertia::render('Storefront/EvcRequestShow', [
            'evcRequest' => $evcRequest ? $evcRequest->toArray() : null,
            'orderId' => $orderId,
            'requestRefNo' => $requestRefNo,
        ]);
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

    private function decryptAES(string $base64CipherText): string
    {
        $key = config('services.valuedesign.aes_key') ?? env('VD_AES_KEY');
        $iv = config('services.valuedesign.aes_iv') ?? env('VD_AES_IV');

        if (!$key || !$iv) {
            Log::warning('DecryptAES: missing key/iv configuration');
            return '';
        }

        $cipherText = base64_decode($base64CipherText, true);
        if ($cipherText === false) {
            Log::error('DecryptAES: base64 decode failed');
            return '';
        }

        $plain = openssl_decrypt($cipherText, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        if ($plain === false) {
            Log::error('DecryptAES: openssl decryption failed');
            return '';
        }

        return $plain;
    }
}

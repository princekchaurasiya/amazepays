<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Services\VDWebApiService;
use Illuminate\Support\Str;
use App\Helpers\AesHelper;
use App\Models\EvcCardItem;
use App\Models\EvcStatus;
use Illuminate\Support\Facades\Http;
use App\Models\Brand;
use App\Models\GetEvcRequest;

class VDWebController extends Controller
{
    protected $vdWebApiService;

    public function __construct(VDWebApiService $vdWebApiService)
    {
        $this->vdWebApiService = $vdWebApiService;
    }

    // Step 1: Generate Token
     public function getToken()
    {
        $url = env('TOKEN_API_URL');
        $distributorId = env('DISTRIBUTOR_ID');

        $response = Http::timeout(20) // 20 seconds
    ->withHeaders([
        'username' => env('API_USERNAME'),
        'password' => env('API_PASSWORD'),
    ])->post($url, [
            'distributor_id' => $distributorId,
        ]);

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

    private function decryptAES($encryptedBase64)
    {
    $key = env('AES_SECRET_KEY');
    $iv = env('AES_IV');

    $ciphertext = base64_decode($encryptedBase64, true);

    if ($ciphertext === false) {
        return 'Base64 decoding failed';
    }

    $decrypted = openssl_decrypt(
        $ciphertext,
        'AES-256-CBC',
        $key,
        OPENSSL_RAW_DATA,
        $iv
    );

    if ($decrypted === false) {
        return 'openssl_decrypt failed (possibly bad key/iv or padding)';
    }

    return $decrypted;
    }
    

    public function getBrandsFromToken()
    {
        $token = $this->getToken();

        if (!$token) {
            return response()->json(['error' => 'Failed to generate token'], 500);
        }

        $brands = $this->vdWebApiService->getBrands($token);

        if ($brands) {
            return response()->json(['brands' => $brands]);
        }
        dd($brands);
        return response()->json(['error' => 'Failed to fetch brands'], 500);
    }


    public function displayBrands()
    {
        $token = $this->getToken();

        if (!$token) {
            return response()->json(['error' => 'Failed to generate token'], 500);
        }

        $brands = $this->vdWebApiService->displayBrands($token);

        if ($brands) {
            return response()->json(['brands' => $brands]);
        }
        return response()->json(['error' => 'Failed to fetch brands'], 500);
    }

    public function fetchStores(VDWebApiService $service)
    {
        $token = $this->testToken();

        if (!$token) {
            return response()->json(['error' => 'Failed to generate token'], 500);
        }
        
        $stores = $service->getStores($token, '');

        if ($stores) {
            return response()->json(['stores' => $stores]);
        }

        return response()->json(['error' => 'Failed to fetch stores'], 500);
    }

    public function storeGetEvcRequest(Request $request)
{
    // Validate and store the raw request
    $data = $request->all();

    // Optionally validate inputs
    $request->validate([
        'order_id' => 'required|string|unique:get_evc_requests',
        'distributor_id' => 'required|string',
        'sku_code' => 'required|string',
        'no_of_card' => 'required|integer|min:1',
        'amount' => 'required|numeric',
        'receiptNo' => 'required|string',
        'reqId' => 'required|string',
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

    $stored = GetEvcRequest::create([
        'order_id'       => $data['order_id'],
        'distributor_id' => $data['distributor_id'],
        'sku_code'       => $data['sku_code'],
        'no_of_card'     => $data['no_of_card'],
        'amount'         => $data['amount'],
        'receipt_no'     => $data['receiptNo'],
        'req_id'         => $data['reqId'],
        'firstname'      => $data['firstname'],
        'lastname'       => $data['lastname'],
        'email'          => $data['email'],
        'mobile_no'      => $data['mobile_no'],
        'address'        => $data['address'],
        'city'           => $data['city'],
        'state'          => $data['state'],
        'country'        => $data['country'],
        'pincode'        => $data['pincode'],
        'curr'           => $data['curr'],
    ]);

    return response()->json([
        'message' => 'GetEVC request stored successfully',
        'data' => $stored
    ]);
}

public function buildPayloadFromDB($recordId)
{
    // Fetch the stored record by ID
    $evcRequest = GetEvcRequest::findOrFail($recordId);

    // Build payload using database values + dynamic IDs
    $payload = [
        'order_id'        => 'ORD-' . strtoupper(Str::random(10)),
        'request_ref_no'  => 'REF-' . strtoupper(Str::random(12)),
        'distributor_id'  => $evcRequest->distributor_id,
        'sku_code'        => $evcRequest->sku_code,
        'no_of_card'      => $evcRequest->no_of_card,
        'amount'          => $evcRequest->amount,
        'receiptNo'       => $evcRequest->receipt_no,
        'reqId'           => $evcRequest->req_id,
        'firstname'       => $evcRequest->firstname,
        'lastname'        => $evcRequest->lastname,
        'email'           => $evcRequest->email,
        'mobile_no'       => $evcRequest->mobile_no,
        'address'         => $evcRequest->address,
        'city'            => $evcRequest->city,
        'state'           => $evcRequest->state,
        'country'         => $evcRequest->country,
        'pincode'         => $evcRequest->pincode,
        'curr'            => $evcRequest->curr,
    ];

    return $payload;
}
    public function requestEvc(VDWebApiService $vdWebApiService)
    {
        // Step 1: Get Token
        $tokenResponse = $vdWebApiService->getToken();
        //dd($tokenResponse);
        $token = $tokenResponse;

        if (!$token) {
            return response()->json(['error' => 'Failed to get token'], 500);
        }

        // Step 2: Create Payload with Unique IDs
        $payload = $this->buildPayloadFromDB(1);
        $payload['amount'] = (float) $payload['amount'];
        $jsonPayload = json_encode($payload);
        //$encryptedPayload = AesHelper::encrypt($jsonPayload);
        //dd($encryptedPayload);

        // Step 3: Call API
        $response = $vdWebApiService->getEvc($token, $jsonPayload);
        if (!$response) {
            return response()->json(['error' => 'Failed to get EVC']);
        }

        //This is tp decrypt Data
        $decryptedData = $this->decryptAES($response['data']);

        return view('evc.success', [
        'orderId' => $response['order_id'],
        'requestRefNo' => $response['request_ref_no'],
        'evcData' => $decryptedData,
        ]);
    }

    public function storeEvcData(array $data)
    {
        if (!isset($data['brand_details'])) {
            return false;
        }

        foreach ($data['brand_details'] as $brandDetail) {
            $brandCode = $brandDetail['voucher_name'];
            $productName = $brandDetail['product_name'];

            foreach ($brandDetail['items'] as $item) {
                EvcCardItem::create([
                    'brand_code' => $brandCode,
                    'product_name' => $productName,
                    'card_no' => $item['getCardNo'],
                    'card_pin' => $item['getCardPin'],
                    'card_status' => $item['getCardStatus'],
                    'expiry_date' => $item['getExpiryDate'],
                    'balance_basic' => $item['balanceBasic'],
                    'balance_bonus' => $item['balanceBonus'],
                    'balance_total' => $item['balanceTotal'],
                    'bonus_given' => $item['bonusGiven'],
                    'deal_no' => $item['dealNo'],
                    'receipt_no' => $item['receiptNo'],
                ]);
            }
        }

        return true;
    }

    public function decryptAndStoreEvc(Request $request)
    {
    $request->validate([
        'encrypted_payload' => 'required|string',
    ]);

    $key = env('AES_KEY');
    $iv = env('AES_IV');
    $encryptedPayload = $request->input('encrypted_payload');

    $decrypted = \App\Helpers\AesHelper::decryptPayload($encryptedPayload, $key, $iv);
    if (!$decrypted) {
        return response()->json(['error' => 'Decryption failed'], 400);
    }

    $data = json_decode($decrypted, true);
    if (!is_array($data)) {
        return response()->json(['error' => 'Invalid JSON structure'], 422);
    }

    $this->storeEvcData($data);

    return response()->json(['message' => 'EVC items stored successfully']);
    }

    public function getEvcStatus(Request $request, VDWebApiService $vdWebApiService)
    {
        $latestRequest = GetEvcRequest::latest()->first();
        $orderId = $latestRequest->order_id;
        $receiptNo = $latestRequest->receipt_no;

        $request->validate([
            'order_id' => 'required|string',
            'request_ref_no' => 'required|string',
        ]);

        $tokenResponse = $vdWebApiService->getToken();
        $token = $tokenResponse['token'] ?? null;

        if (!$token) {
            return response()->json(['error' => 'Token generation failed'], 500);
        }

        $status = $vdWebApiService->getEvcStatus(
            $token,
            $request->order_id,
            $request->request_ref_no
        );

        if (!$status) {
            return response()->json(['error' => 'Failed to fetch EVC status'], 500);
        }
        dd($status);

        return response()->json([
            'status_response' => $status
        ]);
    }

    public function VDgetEvcStatus(Request $request, VDWebApiService $vdWebApiService)
    {
        $request->validate([
        'order_id' => 'required|string',
        'request_ref_no' => 'required|string',
        ]);
        $tokenResponse = $vdWebApiService->getToken();
        /*$token = $tokenResponse['token'] ?? null;

        if (!$token) {
            return back()->with('error', 'Token generation failed.');
        }*/

        $status = $vdWebApiService->getEvcStatus(
            $tokenResponse,
            $request->order_id,
            $request->request_ref_no
        );

        if (!$status) {
            return back()->with('error', 'Failed to fetch EVC status.');
        }

        return view('evc.status', [
        'status' => $status,
        'order_id' => $request->order_id,
        'request_ref_no' => $request->request_ref_no,
    ]);
        // Save to DB
       /* $record = EvcStatus::updateOrCreate(
            [
                'order_id' => $request->order_id,
                'request_ref_no' => $request->request_ref_no,
            ],
            [
                'status' => $status['status'] ?? 'UNKNOWN',
                'details' => $status['details'] ?? [],
            ]
        );

        return redirect()->route('evc.status.view', $record->id);*/
    }


    public function showBrands()
    {
        $brands = $this->getBrandsFromToken();
        $data = $brands->json(); // Returns an associative array

        return view('vdbrands.brands', [
        'brands' => $data['brands'] ?? []
        ]);
    }
}

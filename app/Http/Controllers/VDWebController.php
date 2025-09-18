<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Services\VDWebApiService;
use Illuminate\Support\Str;
use App\Helpers\AesHelper;
use App\Models\EvcCardItem;
use App\Models\EvcStatus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Brand;
use App\Models\GetEvcRequest;
use Illuminate\Support\Facades\Crypt;

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

        if (!$token || !is_string($token)) {
            return response()->json(['error' => 'Failed to generate token'], 500);
        }

        $brands = $this->vdWebApiService->getBrands($token);

        if (is_array($brands) && !empty($brands)) {
            return response()->json(['brands' => $brands]);
        }

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

public function buildPayloadFromDB($recordId = null)
{
    // Fetch the stored record by ID or use latest if not provided
    $evcRequest = $recordId
        ? GetEvcRequest::find($recordId)
        : GetEvcRequest::latest()->first();

    if (!$evcRequest) {
        abort(422, 'No GetEvcRequest record found. Please create one before requesting EVC.');
    }

    // Build payload using database values and persist identifiers
    $payload = [
        'order_id'        => $evcRequest->order_id,
        'request_ref_no'  => $evcRequest->req_id,
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
        $payload = $this->buildPayloadFromDB();
        $payload['amount'] = (float) $payload['amount'];
        $jsonPayload = json_encode($payload);
        //$encryptedPayload = AesHelper::encrypt($jsonPayload);
        //dd($encryptedPayload);

        // Step 3: Call API
        $response = $vdWebApiService->getEvc($token, $jsonPayload);
        if (!$response) {
            return response()->json(['error' => 'Failed to get EVC']);
        }

        // Decrypt data safely
        if (!is_array($response) || !array_key_exists('data', $response) || empty($response['data'])) {
            Log::error('EVC response missing data field', ['response' => $response]);
            return response()->json(['error' => 'EVC response invalid or missing data'], 502);
        }

        $decryptedData = $this->decryptAES($response['data']);

        return view('evc.success', [
        'orderId' => $response['order_id'],
        'requestRefNo' => $response['request_ref_no'],
        'evcData' => $decryptedData,
        ]);
    }

    // Controller Example
public function showEvcDetails(Request $request, VDWebApiService $vdWebApiService)
{
    // Validate the form data
    $request->validate([
        'denomination' => 'required|numeric|min:100|max:10000',
        'quantity' => 'required|integer|min:1|max:10',
        'gift_send_option' => 'required|in:Send as Gift,Buy for Self,send_as_gift,buy_for_self',
        'delivery_mode' => 'required|in:both,email,sms',
        'receiver_name' => 'nullable|string|max:255',
        'receiver_email' => 'nullable|email|max:255',
        'receiver_mobile' => 'nullable|string|max:20',
        'receiver_msg' => 'nullable|string',
    ]);

    // Normalize gift_send_option to snake_case for consistency
    $normalizedGiftOption = $request->gift_send_option;
    if ($normalizedGiftOption === 'Send as Gift') {
        $normalizedGiftOption = 'send_as_gift';
    } elseif ($normalizedGiftOption === 'Buy for Self') {
        $normalizedGiftOption = 'buy_for_self';
    }

    // Store form data in session for later use
    session([
        'denomination' => $request->denomination,
        'quantity' => $request->quantity,
        'gift_send_option' => $normalizedGiftOption,
        'delivery_mode' => $request->delivery_mode,
        'receiver_name' => $request->receiver_name,
        'receiver_email' => $request->receiver_email,
        'receiver_mobile' => $request->receiver_mobile,
        'receiver_msg' => $request->receiver_msg,
    ]);

    // Step 1: Get Token
    $tokenResponse = $vdWebApiService->getToken();
    //dd($tokenResponse);
    $token = $tokenResponse;

    if (!$token) {
        return response()->json(['error' => 'Failed to get token'], 500);
    }

    // Step 2: Create Payload with Unique IDs
    $payload = $this->buildPayloadFromDB();
    $payload['amount'] = (float) $request->denomination; // Use form denomination instead of DB
    $jsonPayload = json_encode($payload);
    //$encryptedPayload = AesHelper::encrypt($jsonPayload);
    //dd($encryptedPayload);

    // Step 3: Call API
    $response = $vdWebApiService->getEvc($token, $jsonPayload);
    if (!$response) {
        return response()->json(['error' => 'Failed to get EVC']);
    }

    // Decrypt data safely
    if (!is_array($response) || !array_key_exists('data', $response) || empty($response['data'])) {
        Log::error('EVC response missing data field (showEvcDetails)', ['response' => $response]);
        return back()->with('error', 'EVC response invalid or missing data. Please try again.');
    }
    $decryptedData = $this->decryptAES($response['data']);
    
    // Parse the decrypted data to extract specific fields
    $evcArray = json_decode($decryptedData, true);
    $items = [];
    
    if (isset($evcArray['brand_details'][0]['items'])) {
        $items = $evcArray['brand_details'][0]['items'];
    }

    return view('evc.success', [
        'orderId' => $response['order_id'],
        'requestRefNo' => $response['request_ref_no'],
        'items' => $items,
    ]);
}

public function evcDetails($orderId, $requestRefNo, VDWebApiService $vdWebApiService)
{
    // Get Token
    $token = $vdWebApiService->getToken();
    if (!$token || !is_string($token)) {
        return response()->json(['error' => 'Failed to get token'], 500);
    }

    // Fetch Activated EVC details for provided IDs
    $activatedEvc = $vdWebApiService->getActivatedEvc($token, $orderId, $requestRefNo);
    if (!$activatedEvc) {
        Log::error('Activated EVC fetch failed', ['order_id' => $orderId, 'request_ref_no' => $requestRefNo]);
        return response()->json(['error' => 'Failed to fetch EVC details'], 502);
    }

    $decryptedData = null;
    if (is_array($activatedEvc) && !empty($activatedEvc['data'])) {
        $decryptedData = $this->decryptAES($activatedEvc['data']);
    } else {
        Log::warning('Activated EVC response missing data field', ['response' => $activatedEvc]);
    }

    return view('evc.activated', [
        'evc' => $activatedEvc,
        'order_id' => $orderId,
        'request_ref_no' => $requestRefNo,
        'decryptedData' => $decryptedData
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

    public function VDgetActivatedEvc(Request $request, VDWebApiService $vdWebApiService)
{
    // Validate incoming request
    $request->validate([
        'order_id' => 'required|string',
        'request_ref_no' => 'required|string',
    ]);

    // Get token from VD API service
    $tokenResponse = $vdWebApiService->getToken();
    /*$token = $tokenResponse['token'] ?? null;

    if (!$token) {
        return back()->with('error', 'Token generation failed.');
    }*/

    // Call the getactivatedevc endpoint
    $activatedEvc = $vdWebApiService->getActivatedEvc(
        $tokenResponse,
        $request->order_id,
        $request->request_ref_no
    );

    if (!$activatedEvc) {
        return back()->with('error', 'Failed to fetch activated EVC.');
    }

    // 🔹 Decrypt data here (replace with actual decryption method)
    $decryptedData = null;
    if (!empty($activatedEvc['data'])) {
        $decryptedData = $this->decryptAES($activatedEvc['data']);
        dd($decryptedData);
    }


    // Return the result to a view
    return view('evc.activated', [
        'evc' => $activatedEvc,
        'order_id' => $request->order_id,
        'request_ref_no' => $request->request_ref_no,
        'decryptedData' => $decryptedData
    ]);
}



    public function showBrands()
    {
        $brands = $this->getBrandsFromToken();
        $data = $brands->getData(true); // Returns an associative array

        return view('vdbrands.brands', [
        'brands' => $data['brands'] ?? []
        ]);
    }

    public function getWalletBalance()
    {
        $token = $this->getToken(); // Returns string like '1D2IZ6A7V2MQ...'

        $response = Http::withHeaders([
            'token' => $token, 
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post('https://at.valuedesign.co.in/distributor/getwalletbalance/', [
            'distributor_id' => 'VDAmazepay97',
        ]);

        // Debug if it fails
        if (!$response->successful()) {
            dd('Request failed:', $response->status(), $response->body());
        }

        $data = $response->json();

        return view('wallet.balance', compact('data'));
    }

}

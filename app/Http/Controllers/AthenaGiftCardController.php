<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Services\AthenaGiftCardService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AthenaGiftCardController extends Controller
{
    protected $giftCardService;

    public function __construct(AthenaGiftCardService $giftCardService)
    {
        $this->giftCardService = $giftCardService;
    }

    public function index(Request $request)
    {
        $brand = $request->query('brand');
        $pageNumber = $request->query('pageNumber', 0);
        $pageSize = $request->query('pageSize', 20);

        try {
            $giftCards = $this->giftCardService->listGiftCards($brand, $pageNumber, $pageSize);
            return response()->json($giftCards);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function showGiftcards()
{
    $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('LYSTO_API_KEY'),
            'partnerid' => env('LYSTO_PARTNER_ID'),
        ])->get('https://stagedistapi.lysto.io/api/v1/giftcards'); // Replace with actual URL
    $data = $response->json();
    if ($data['status'] === 200) {
        return view('giftcards', ['giftcards' => $data['giftcards']]);
    } else {
        abort(500, 'Failed to fetch giftcards');
    }
}

public function showGiftcards2($id)
{
    try {
    $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('LYSTO_API_KEY'),
            'partnerid' => env('LYSTO_PARTNER_ID'),
        ])->get('https://stagedistapi.lysto.io/api/v1/giftcards/{$id}/skus'); // Replace with actual URL
    $data = $response->json();
    Log::info('API Response:', [
    'status' => $response->status(),
    'body' => $response->body(),
]);
    dd($data);
    if ($data['status'] === 200) {
        return view('giftcard-details', ['skus' => $skus, 'giftcardId' => $id]);
    } else {
        abort(500, 'Failed to fetch giftcards');
    }
    } catch (\Exception $e) {
        \Log::error('Exception while fetching SKUs', [
            'message' => $e->getMessage(),
        ]);
        abort(500, 'Internal server error');
    }

}

    public function getSkus($giftcard_id)
    {
        try {
            $response = $this->giftCardService->getSkusByGiftCardId($giftcard_id);
            //return response()->json($response['skus']);
             $skus = $response['skus'];
            return view('giftcard-details', ['skus' => $skus, 'giftcardId' => $giftcard_id]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function purchaseView(Request $request)
{
    $giftcardId = $request->query('giftcard_id');
    $skuId = $request->query('sku_id');
    // You may fetch additional SKU or giftcard data if needed
    return view('giftcard-purchase', compact('giftcardId', 'skuId'));
}

    public function purchase(Request $request)
    {

    try {
        $walletResponse = $this->giftCardService->getWalletBalance();

        // If balance is not greater than 0, stop and return with error
        if (!isset($walletResponse['balance']) || $walletResponse['balance'] <= 0) {
            return back()->withErrors(['Your wallet balance is insufficient to complete the purchase.']);
        }
    } catch (\Exception $e) {
        return back()->withErrors(['Failed to retrieve wallet balance: ' . $e->getMessage()]);
    }

    try {
        $payload = [
            'merchant_order_request_id' => (string) Str::uuid(),
            'giftcard_id' => "1",
            'sku_id' => "1",
            'quantity' => 1,
            'currency' => 'INR',
        ];

        $response = $this->giftCardService->purchaseGiftCard($payload);
        return view('giftcard.success', [
            'gift_code' => $response['gift_code'],
            'order_id'  => $response['order_id'],
        ]);
        // Encrypt the response before returning it to the frontend
        /*$encryptedResponse = encrypt($response);
        return response()->json(['data' => $encryptedResponse]);*/

    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
    }

    public function getOrder(Request $request, $orderId)
    {
        try {
            $order = $this->giftCardService->getOrderDetails($orderId);
            return response()->json($order);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getWalletBalance()
    {
        try {
            $balance = $this->giftCardService->getWalletBalance();
            return response()->json($balance);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

}

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
    // View removed - now returns JSON only (admin-only route)
    $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('LYSTO_API_KEY'),
            'partnerid' => env('LYSTO_PARTNER_ID'),
        ])->get('https://stagedistapi.lysto.io/api/v1/giftcards');
    $data = $response->json();
    if ($data['status'] === 200) {
        return response()->json($data);
    } else {
        return response()->json(['error' => 'Failed to fetch giftcards'], 500);
    }
}

public function showGiftcards2($id)
{
    // View removed - now returns JSON only (admin-only route)
    // This method appears to be duplicate/unused - keeping for backward compatibility
    try {
        $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('LYSTO_API_KEY'),
                'partnerid' => env('LYSTO_PARTNER_ID'),
            ])->get('https://stagedistapi.lysto.io/api/v1/giftcards/' . $id . '/skus');
        $data = $response->json();
        Log::info('API Response:', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);
        if ($data['status'] === 200) {
            return response()->json($data);
        } else {
            return response()->json(['error' => 'Failed to fetch giftcards'], 500);
        }
    } catch (\Exception $e) {
        Log::error('Exception while fetching SKUs', [
            'message' => $e->getMessage(),
        ]);
        return response()->json(['error' => 'Internal server error'], 500);
    }
}

    public function getSkus($giftcard_id)
    {
        // View removed - now returns JSON only (admin-only route)
        try {
            $response = $this->giftCardService->getSkusByGiftCardId($giftcard_id);
            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function purchaseView(Request $request)
{
    // View removed - purchase should be done via API only
    // This method now returns JSON with purchase form data
    $giftcardId = $request->query('giftcard_id');
    $skuId = $request->query('sku_id');
    return response()->json([
        'giftcard_id' => $giftcardId,
        'sku_id' => $skuId,
        'message' => 'Use POST /admin/lysto/giftcard/purchase to purchase gift cards'
    ]);
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
        return back()->withErrors(['Failed to retrieve wallet balance: ']);
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
        // View removed - now returns JSON only (admin-only route)
        return response()->json([
            'success' => true,
            'gift_code' => $response['gift_code'],
            'order_id'  => $response['order_id'],
            'message' => 'Gift card purchased successfully'
        ]);

    } catch (\Exception $e) {
        return response()->json([
        'error' => 'PurchaseError',
        'message' => 'Gift card purchase failed',
        'description' => $e->getMessage(),
        ], 500);
    }
    }

    public function getOrder(Request $request)
{
    $orderId = $request->query('order_id');
    $merchantOrderRequestId = $request->query('merchant_order_request_id');

    try {
        $order = $this->giftCardService->getOrderDetails($orderId, $merchantOrderRequestId);
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

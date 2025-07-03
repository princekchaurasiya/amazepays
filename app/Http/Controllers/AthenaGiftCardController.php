<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Services\AthenaGiftCardService;

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

    public function getSkus($giftcard_id)
    {
        try {
            $response = $this->giftCardService->getSkusByGiftCardId($giftcard_id);
            return response()->json($response['skus']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function purchase(Request $request)
    {
        $validated = $request->validate([
            'order_request_id' => 'required|string',
            'brand_id' => 'required|string',
            'sku_id' => 'required|string',
            'quantity' => 'required|integer|min:1',
            'currency' => 'required|string',
        ]);

        try {
            $response = $this->giftCardService->purchaseGiftCard($validated);
            return response()->json($response);
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

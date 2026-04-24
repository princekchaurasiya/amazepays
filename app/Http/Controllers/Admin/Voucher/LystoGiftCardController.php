<?php

namespace App\Http\Controllers\Admin\Voucher;

use App\Http\Controllers\Controller;
use App\Http\Services\AthenaGiftCardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class LystoGiftCardController extends Controller
{
    public function __construct(private readonly AthenaGiftCardService $giftCardService) {}

    public function index(Request $request)
    {
        $brand = $request->query('brand');
        $pageNumber = (int) $request->query('pageNumber', 0);
        $pageSize = (int) $request->query('pageSize', 20);

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
            'Authorization' => 'Bearer '.config('lysto.api_key'),
            'partnerid' => config('lysto.partner_id'),
        ])->get(rtrim((string) config('lysto.base_url'), '/').'/giftcards');

        $data = $response->json();
        if (($data['status'] ?? null) === 200) {
            return response()->json($data);
        }

        return response()->json(['error' => 'Failed to fetch giftcards'], 500);
    }

    public function showGiftcards2($id)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.config('lysto.api_key'),
                'partnerid' => config('lysto.partner_id'),
            ])->get(rtrim((string) config('lysto.base_url'), '/')."/giftcards/{$id}/skus");

            $data = $response->json();
            Log::info('API Response:', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            if (($data['status'] ?? null) === 200) {
                return response()->json($data);
            }

            return response()->json(['error' => 'Failed to fetch giftcards'], 500);
        } catch (\Exception $e) {
            Log::error('Exception while fetching SKUs', [
                'message' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    public function getSkus($giftcard_id)
    {
        try {
            $response = $this->giftCardService->getSkusByGiftCardId($giftcard_id);

            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function purchaseView(Request $request)
    {
        $giftcardId = $request->query('giftcard_id');
        $skuId = $request->query('sku_id');

        return response()->json([
            'giftcard_id' => $giftcardId,
            'sku_id' => $skuId,
            'message' => 'Use POST /admin/lysto/giftcard/purchase to purchase gift cards',
        ]);
    }

    public function purchase(Request $request)
    {
        try {
            $walletResponse = $this->giftCardService->getWalletBalance();

            if (! isset($walletResponse['balance']) || $walletResponse['balance'] <= 0) {
                return back()->withErrors(['Your wallet balance is insufficient to complete the purchase.']);
            }
        } catch (\Exception) {
            return back()->withErrors(['Failed to retrieve wallet balance: ']);
        }

        try {
            $payload = [
                'merchant_order_request_id' => (string) Str::uuid(),
                'giftcard_id' => '1',
                'sku_id' => '1',
                'quantity' => 1,
                'currency' => 'INR',
            ];

            $response = $this->giftCardService->purchaseGiftCard($payload);

            return response()->json([
                'success' => true,
                'gift_code' => $response['gift_code'],
                'order_id' => $response['order_id'],
                'message' => 'Gift card purchased successfully',
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


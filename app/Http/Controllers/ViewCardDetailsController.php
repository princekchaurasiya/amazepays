<?php

namespace App\Http\Controllers;

use App\Helpers\ApiSignatureHelper;
use App\Helpers\ProductImageHelper;
use App\Models\Order;
use App\Models\Product;
use App\Services\Order\WoohooGiftCardPersister;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ViewCardDetailsController extends Controller
{
    public function index(Request $request, $orderId = null)
    {
        try {
            // Get orderId from route parameter if not provided in request
            $orderId = $orderId ?? $request->input('orderId');

            if (! $orderId) {
                return redirect()->back()->with('error', 'Invalid order ID');
            }

            // Get order with line items + catalog product for imagery; prefer normalized gift_cards for card data.
            $orderData = Order::query()
                ->with(['giftCards', 'items.product'])
                ->where('woohoo_order_id', $orderId)
                ->where('user_id', auth()->id()) // Ensure user can only view their own orders
                ->firstOrFail();

            $cardsArray = app(WoohooGiftCardPersister::class)->displayCardsForOrder($orderData);

            // If cards are still empty, fetch from Woohoo API
            // First check order status using refno, then fetch cards if COMPLETE
            if (empty($cardsArray) && ! empty($orderData->refno)) {
                Log::info('Cards not found in database, checking order status and fetching from Woohoo API', [
                    'refno' => $orderData->refno,
                    'woohoo_order_id' => $orderData->woohoo_order_id,
                ]);

                try {
                    // Step 1: Check order status using refno
                    $statusResponse = $this->checkOrderStatus($orderData->refno);

                    if (isset($statusResponse['status']) && $statusResponse['status'] === 'COMPLETE') {
                        // Step 2: Fetch cards using orderId from status response or woohoo_order_id
                        $woohooOrderId = $statusResponse['orderId'] ?? $orderData->woohoo_order_id;

                        if ($woohooOrderId) {
                            $fetchedCards = $this->fetchCardsFromWoohoo($woohooOrderId);

                            if (! empty($fetchedCards)) {
                                $cardsArray = $fetchedCards;

                                // Persist fetched cards to normalized gift_cards.
                                try {
                                    app(WoohooGiftCardPersister::class)->syncWoohooCards($orderData->fresh(), $cardsArray);
                                    Log::info('✅ Cards fetched from Woohoo API and saved to database', [
                                        'order_id' => $orderData->id,
                                    ]);
                                } catch (\Exception $e) {
                                    Log::warning('Failed to save fetched cards to database: '.$e->getMessage());
                                }
                            } else {
                                Log::warning('Cards array is empty after fetching from Woohoo API', [
                                    'woohoo_order_id' => $woohooOrderId,
                                ]);
                            }
                        }
                    } elseif (isset($statusResponse['status'])) {
                        Log::info('Order status is not COMPLETE, cannot fetch cards yet', [
                            'status' => $statusResponse['status'],
                            'refno' => $orderData->refno,
                        ]);
                    } else {
                        Log::warning('Order status check failed or returned invalid response', [
                            'refno' => $orderData->refno,
                            'response' => $statusResponse,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to fetch cards from Woohoo API: '.$e->getMessage(), [
                        'refno' => $orderData->refno,
                        'woohoo_order_id' => $orderData->woohoo_order_id,
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }

            if (empty($cardsArray)) {
                Log::error('No card data available', [
                    'order_id' => $orderData->id,
                    'woohoo_order_id' => $orderData->woohoo_order_id,
                ]);

                return redirect()->back()->with('error', 'Card details are not available. Please contact support.');
            }

            // Get product image using ProductImageHelper (Phase 3: product via first order line).
            $productImage = null;
            $lineProduct = $orderData->items->first()?->product;
            if ($lineProduct instanceof Product) {
                $productImage = ProductImageHelper::getProductImage($lineProduct);
            }

            return Inertia::render('Storefront/OrderDetail', [
                'cardArray' => $cardsArray,
                'productImage' => $productImage,
                'order' => $orderData->only([
                    'id', 'refno', 'woohoo_order_id', 'order_status', 'product_name',
                    'denomination', 'quantity', 'grand_payable_amount',
                ]),
            ]);

        } catch (\Exception $e) {
            Log::error('View card details error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Unable to retrieve order details');
        }
    }

    /**
     * Check order status from Woohoo API using reference number
     */
    private function checkOrderStatus($refno)
    {
        try {
            $requestHttpMethod = 'GET';
            $absApiUrl = 'https://'.config('woohoo.host').'/rest/v3/order/'.$refno.'/status';
            $clientSecret = config('woohoo.client_secret');
            $bearerToken = config('woohoo.bearer_token');
            $signature = ApiSignatureHelper::generateSignature('', $requestHttpMethod, $absApiUrl, $clientSecret);
            $dateAtClient = Carbon::now()->toIso8601String();

            Log::info('Checking order status for refno', ['refno' => $refno]);

            $response = Http::acceptJson()
                ->timeout(10)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer '.$bearerToken,
                    'Accept' => '*/*',
                    'dateAtClient' => $dateAtClient,
                    'signature' => $signature,
                ])
                ->get($absApiUrl);

            if ($response->successful()) {
                $responseData = $response->json();

                // Handle both array and object responses
                if (is_object($responseData)) {
                    $responseData = json_decode(json_encode($responseData), true);
                }

                Log::info('Order status check response', [
                    'refno' => $refno,
                    'status' => $responseData['status'] ?? 'unknown',
                ]);

                return $responseData;
            } else {
                Log::error('Order status check failed', [
                    'refno' => $refno,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                return ['status' => 'error', 'message' => 'Status check failed'];
            }
        } catch (ConnectionException $e) {
            Log::error('Connection error while checking order status: '.$e->getMessage());

            return ['status' => 'error', 'message' => 'Connection failed'];
        } catch (\Exception $e) {
            Log::error('Error checking order status: '.$e->getMessage());

            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Fetch cards from Woohoo API
     */
    private function fetchCardsFromWoohoo($woohooOrderId)
    {
        try {
            $clientSecret = config('woohoo.client_secret');
            $bearerToken = config('woohoo.bearer_token');
            $apiUrl = 'https://'.config('woohoo.host');
            $absApiUrl = "$apiUrl/rest/v3/order/{$woohooOrderId}/cards";
            $requestBody = '';
            $requestHttpMethod = 'GET';
            $dateAtClient = Carbon::now()->toIso8601String();
            $signature = ApiSignatureHelper::generateSignature($requestBody, $requestHttpMethod, $absApiUrl, $clientSecret);

            $response = Http::acceptJson()
                ->withToken($bearerToken)
                ->withHeaders([
                    'signature' => $signature,
                    'dateAtClient' => $dateAtClient,
                ])
                ->get($absApiUrl);

            if ($response->successful()) {
                $responseData = $response->json();

                // Handle both array and object responses
                if (is_object($responseData)) {
                    $responseData = json_decode(json_encode($responseData), true);
                }

                // Extract cards from response
                if (isset($responseData['cards']) && is_array($responseData['cards'])) {
                    return $responseData['cards'];
                } elseif (isset($responseData['cardNumber']) || isset($responseData['cardnumber'])) {
                    // Handle single card response
                    return [[
                        'cardNumber' => $responseData['cardNumber'] ?? $responseData['cardnumber'] ?? null,
                        'cardPin' => $responseData['cardPin'] ?? $responseData['cardpin'] ?? null,
                        'amount' => $responseData['amount'] ?? null,
                        'activationCode' => $responseData['activationCode'] ?? $responseData['activation_code'] ?? null,
                        'activationUrl' => $responseData['activationUrl'] ?? $responseData['activation_url'] ?? null,
                        'validity' => $responseData['validity'] ?? null,
                    ]];
                }
            } else {
                Log::error('Woohoo API card fetch failed', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
            }
        } catch (ConnectionException $e) {
            Log::error('Connection error while fetching cards from Woohoo API: '.$e->getMessage());
        } catch (\Exception $e) {
            Log::error('Error fetching cards from Woohoo API: '.$e->getMessage());
        }

        return [];
    }
}

<?php

namespace App\Http\Controllers;
use App\Models\QsOrder;
use App\Models\QsProduct;
use App\Helpers\CommonHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;

class ViewCardDetailsController extends Controller
{
    public function index(Request $request, $orderId = null)
    {
        try {
            // Get orderId from route parameter if not provided in request
            $orderId = $orderId ?? $request->input('orderId');
            
            if (!$orderId) {
                return redirect()->back()->with('error', 'Invalid order ID');
            }

            // Get order with product details
            $orderData = QsOrder::with('product')
                ->where('woohoo_order_id', $orderId)
                ->where('user_id', auth()->id()) // Ensure user can only view their own orders
                ->firstOrFail();

            // Try to decrypt card data from database
            $cardsData = null;
            $cardsArray = [];
            
            if (!empty($orderData->cards)) {
                try {
                    // Try to decrypt encrypted cards
                    $cardsData = json_decode(decrypt($orderData->cards, env('ENCRYPTION_KEY')), true);
                    if (is_array($cardsData)) {
                        $cardsArray = $cardsData;
                    }
                } catch (\Exception $e) {
                    // Decryption failed - might be plain JSON or invalid encryption
                    Log::warning('Card decryption failed, trying plain JSON: ' . $e->getMessage());
                    try {
                        // Try to parse as plain JSON
                        $cardsData = json_decode($orderData->cards, true);
                        if (is_array($cardsData)) {
                            $cardsArray = $cardsData;
                        }
                    } catch (\Exception $e2) {
                        Log::warning('Plain JSON parse also failed: ' . $e2->getMessage());
                    }
                }
            }

            // If cards are still empty, fetch from Woohoo API
            // First check order status using refno, then fetch cards if COMPLETE
            if (empty($cardsArray) && !empty($orderData->refno)) {
                Log::info('Cards not found in database, checking order status and fetching from Woohoo API', [
                    'refno' => $orderData->refno,
                    'woohoo_order_id' => $orderData->woohoo_order_id
                ]);
                
                try {
                    // Step 1: Check order status using refno
                    $statusResponse = $this->checkOrderStatus($orderData->refno);
                    
                    if (isset($statusResponse['status']) && $statusResponse['status'] === 'COMPLETE') {
                        // Step 2: Fetch cards using orderId from status response or woohoo_order_id
                        $woohooOrderId = $statusResponse['orderId'] ?? $orderData->woohoo_order_id;
                        
                        if ($woohooOrderId) {
                            $fetchedCards = $this->fetchCardsFromWoohoo($woohooOrderId);
                            
                            if (!empty($fetchedCards)) {
                                $cardsArray = $fetchedCards;
                                
                                // Save fetched cards to database (encrypted)
                                try {
                                    $orderData->cards = encrypt(json_encode($cardsArray), env('ENCRYPTION_KEY'));
                                    $orderData->save();
                                    Log::info('✅ Cards fetched from Woohoo API and saved to database', [
                                        'order_id' => $orderData->id
                                    ]);
                                } catch (\Exception $e) {
                                    Log::warning('Failed to save fetched cards to database: ' . $e->getMessage());
                                }
                            } else {
                                Log::warning('Cards array is empty after fetching from Woohoo API', [
                                    'woohoo_order_id' => $woohooOrderId
                                ]);
                            }
                        }
                    } elseif (isset($statusResponse['status'])) {
                        Log::info('Order status is not COMPLETE, cannot fetch cards yet', [
                            'status' => $statusResponse['status'],
                            'refno' => $orderData->refno
                        ]);
                    } else {
                        Log::warning('Order status check failed or returned invalid response', [
                            'refno' => $orderData->refno,
                            'response' => $statusResponse
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to fetch cards from Woohoo API: ' . $e->getMessage(), [
                        'refno' => $orderData->refno,
                        'woohoo_order_id' => $orderData->woohoo_order_id,
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            if (empty($cardsArray)) {
                Log::error('No card data available', [
                    'order_id' => $orderData->id,
                    'woohoo_order_id' => $orderData->woohoo_order_id
                ]);
                return redirect()->back()->with('error', 'Card details are not available. Please contact support.');
            }

            // Get product image using CommonHelper
            $productImage = null;
            if ($orderData->product) {
                $productImage = CommonHelper::getProductImage($orderData->product);
            }

            return view('order.viewCard')->with([
                'cardArray' => $cardsArray,
                'productImage' => $productImage,
                'order' => $orderData
            ]);

        } catch (\Exception $e) {
            Log::error('View card details error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
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
            $absApiUrl = 'https://' . setting('api.woohoo_url') . '/rest/v3/order/' . $refno . '/status';
            $clientSecret = setting('api.qs_clientSecret');
            $bearerToken = setting('api.bearer_token');
            $signature = CommonHelper::generateSignature('', $requestHttpMethod, $absApiUrl, $clientSecret);
            $dateAtClient = Carbon::now()->toIso8601String();

            Log::info('Checking order status for refno', ['refno' => $refno]);

            $response = Http::acceptJson()
                ->timeout(10)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $bearerToken,
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
                    'status' => $responseData['status'] ?? 'unknown'
                ]);

                return $responseData;
            } else {
                Log::error('Order status check failed', [
                    'refno' => $refno,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return ['status' => 'error', 'message' => 'Status check failed'];
            }
        } catch (ConnectionException $e) {
            Log::error('Connection error while checking order status: ' . $e->getMessage());
            return ['status' => 'error', 'message' => 'Connection failed'];
        } catch (\Exception $e) {
            Log::error('Error checking order status: ' . $e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Fetch cards from Woohoo API
     */
    private function fetchCardsFromWoohoo($woohooOrderId)
    {
        try {
            $clientSecret = setting('api.qs_clientSecret');
            $bearerToken = setting('api.bearer_token');
            $apiUrl = 'https://' . setting('api.woohoo_url');
            $absApiUrl = "$apiUrl/rest/v3/order/{$woohooOrderId}/cards";
            $requestBody = '';
            $requestHttpMethod = 'GET';
            $dateAtClient = Carbon::now()->toIso8601String();
            $signature = CommonHelper::generateSignature($requestBody, $requestHttpMethod, $absApiUrl, $clientSecret);

            $response = Http::acceptJson()
                ->withToken($bearerToken)
                ->withHeaders([
                    'signature' => $signature,
                    'dateAtClient' => $dateAtClient
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
                    'response' => $response->body()
                ]);
            }
        } catch (ConnectionException $e) {
            Log::error('Connection error while fetching cards from Woohoo API: ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Error fetching cards from Woohoo API: ' . $e->getMessage());
        }

        return [];
    }
}

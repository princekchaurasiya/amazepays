<?php

namespace App\Http\Controllers;

use App\Models\KGenOrder;
use App\Models\KgenProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

class KGenOrderController extends Controller
{
    public function store(Request $request)
    {
        if (! $request->has('variant_id')) {
            return kgenError('invalid variant ID', 'BAD_REQUEST');
        }

        if (! auth()->check()) {
            return kgenError('unauthenticated', 'UNAUTHORIZED');
        }

        if (! auth()->user()->hasRole('admin')) {
            return kgenError("Insufficient permissions: You don't have access to this resource", 'FORBIDDEN');
        }

        if (env('dpID') == '') {
            return kgenError('forbidden: param: admin user does not have access to DP: INVALID_DP_ID', 'FORBIDDEN');
        }

        $product = KgenProduct::latest()->first();

        if (! $product) {
            return kgenError('Record not found', 'RECORD_NOT_FOUND', ['reason' => 'No matching record']);
        }

        // Admin direct order logic here if needed
        return response()->json(['message' => 'Admin store endpoint']);
    }

    public function showForm(Request $request)
    {
        return Inertia::render('Storefront/KGen/PlaceOrder', [
            'variantId' => $request->query('variantId', ''),
            'mrp' => $request->query('mrp', ''),
        ]);
    }

    public function placeOrder(Request $request)
    {
        if (! auth()->check()) {
            return back()->withErrors(['error' => 'Please login to place order']);
        }

        $userId = auth()->id();
        $validated = $request->validate(['variantId' => 'required|string']);

        $dpValue = env('dpID');

        // Check balance
        $balanceResponse = Http::withHeaders([
            'x-client-id' => env('EXLR8_USER_ID'),
            'x-client-secret' => env('EXLR8_USER_SECRET'),
        ])->get(env('EXLR8_BASE_URL').'/delivery-partners/'.$dpValue.'/wallet/balance');

        if (! $balanceResponse->successful()) {
            return back()->withErrors(['error' => 'Unable to check wallet balance at the moment. Please try again later.']);
        }

        $balanceData = $balanceResponse->json();
        $availableBalance = $balanceData['balance'] ?? 0;

        // Get product price (for balance check only)
        $priceResponse = Http::withHeaders([
            'x-client-id' => env('EXLR8_USER_ID'),
            'x-client-secret' => env('EXLR8_USER_SECRET'),
        ])->get(env('EXLR8_BASE_URL').'/products/delivery-partners/'.env('dpID'));

        if (! $priceResponse->successful()) {
            return back()->withErrors(['error' => 'Unable to fetch product price.']);
        }

        $priceData = $priceResponse->json();
        $productPrice = $priceData['variants']['price'] ?? 0;

        if ($availableBalance < $productPrice) {
            return back()->withErrors([
                'error' => "Insufficient balance. Your wallet has {$availableBalance}, but the product costs {$productPrice}.",
            ]);
        }

        // Place order
        $externalRefID = 'ORDER_'.strtoupper(Str::random(6));
        $response = Http::withHeaders([
            'x-client-id' => env('EXLR8_USER_ID'),
            'x-client-secret' => env('EXLR8_USER_SECRET'),
            'Content-Type' => 'application/json',
        ])->post(env('EXLR8_BASE_URL').'/orders/b2b/direct-checkout', [
            'dpID' => $dpValue,
            'variantID' => $validated['variantId'],
            'externalRefID' => $externalRefID,
        ]);

        $data = $response->json();
        $dproductprice = $data['totalAmount'] ?? 0; // ✅ Use API response as-is

        // Handle response based on status
        switch ($data['status'] ?? null) {
            case 'COMPLETED':
                $pendingOrder = KGenOrder::create([
                    'user_id' => $userId,
                    'variant_id' => $validated['variantId'],
                    'external_ref' => $externalRefID,
                    'mrp' => $dproductprice,           // ✅ From API response
                    'payable_amount' => $dproductprice, // ✅ From API response
                    'api_response' => json_encode($data),
                    'status' => 'COMPLETED',
                ]);

                session([
                    'pending_order_id' => $pendingOrder->id,
                    'variant_id' => $validated['variantId'],
                    'payable_amount' => $dproductprice,  // ✅ Fixed: Use API response
                ]);

                Log::info('Order placed COMPLETED', ['order details' => $pendingOrder]);

                return redirect()->route('kgen.payment.initiate', [
                    'order_id' => $pendingOrder->id,
                    'amount' => $dproductprice,        // ✅ Fixed: Use API response
                    'variant_id' => $validated['variantId'],
                ]);

            case 'PROCESSED':
                $pendingOrder = KGenOrder::create([
                    'user_id' => auth()->id(),
                    'variant_id' => $validated['variantId'],
                    'external_ref' => $externalRefID,
                    'mrp' => $dproductprice,
                    'payable_amount' => $dproductprice,
                    'api_response' => json_encode($data),
                    'status' => $data['status'] ?? 'PROCESSED',
                ]);

                session([
                    'pending_order_id' => $pendingOrder->id,
                    'variant_id' => $validated['variantId'],
                    'payable_amount' => $dproductprice,
                ]);

                Log::info('Order placed PROCESSED', ['order details' => $pendingOrder]);

                return redirect()->route('kgen.payment.initiate', [
                    'order_id' => $pendingOrder->id,
                    'amount' => $dproductprice,
                    'variant_id' => $validated['variantId'],
                ]);

            case 'FAILED':
                return back()->withErrors([
                    'error' => 'Order failed. Please try again later.',
                    'orderId' => $data['orderID'] ?? null,
                ])->withInput();

            default:
                return back()->withErrors(['error' => 'Unexpected response from API.'])->withInput();
        }
    }

       public function listOrders()
    {
        $orders = KGenOrder::latest()->paginate(10);

        return Inertia::render('Storefront/KGen/Orders', [
            'orders' => $orders,
        ]);
    }

    public function getOrders(Request $request)
    {
        $queryParams = [];
        if ($request->filled('externalRef')) {
            $queryParams['externalRef'] = $request->externalRef;
        } elseif ($request->filled('orderId')) {
            $queryParams['orderId'] = $request->orderId;
        } elseif ($request->filled('pagination')) {
            $queryParams['nextCursor'] = $request->nextCursor;
            $queryParams['limit'] = $request->limit ?? 10;
        }

        $dpId = env('dpID');
        $response = Http::withHeaders([
            'x-client-id' => env('EXLR8_USER_ID'),
            'x-client-secret' => env('EXLR8_USER_SECRET'),
        ])->get(str_replace('{dpID}', $dpId, env('EXLR8_BASE_URL').'/orders/b2b/delivery-partners/{dpID}'), $queryParams);

        if ($response->successful()) {
            if (isset($response->json()['order'])) {
                return Inertia::render('Storefront/KGen/OrderShow', [
                    'order' => $response->json()['order'],
                ]);
            }

            return Inertia::render('Storefront/KGen/OrderList', [
                'orders' => $response->json()['orders'] ?? [],
            ]);
        }

        return back()->withErrors(['error' => 'Failed to fetch orders']);
    }

    public function getOrders2(Request $request)
    {
        $dpId = env('dpID');
        if ($request->filled('orderId')) {
            $response = Http::withHeaders([
                'x-client-id' => env('EXLR8_USER_ID'),
                'x-client-secret' => env('EXLR8_USER_SECRET'),
            ])->get(str_replace(['{dpID}', '{orderID}'], [$dpId, $request->orderId], env('EXLR8_BASE_URL').'/orders/b2b/delivery-partners/{dpID}/{orderID}'));
        }

        if ($response->successful() && isset($response->json()['order'])) {
            return Inertia::render('Storefront/KGen/OrderShow', [
                'order' => $response->json()['order'],
            ]);
        }

        return back()->withErrors(['error' => 'Failed to fetch orders']);
    }

    public function getOrders3(Request $request)
    {
        $dpId = env('dpID');
        if ($request->filled('externalRef')) {
            $response = Http::withHeaders([
                'x-client-id' => env('EXLR8_USER_ID'),
                'x-client-secret' => env('EXLR8_USER_SECRET'),
            ])->get(str_replace(['{dpID}', '{externalRef}'], [$dpId, $request->externalRef], env('EXLR8_BASE_URL').'/orders/b2b/delivery-partners/{dpID}/external-ref/{externalRef}'));
        }

        if ($response->successful() && isset($response->json()['order'])) {
            return Inertia::render('Storefront/KGen/OrderShow', [
                'order' => $response->json()['order'],
            ]);
        }

        return back()->withErrors(['error' => 'Failed to fetch orders']);
    }

    public function insufficientBalance($message = 'The purchase could not be completed due to insufficient balance.'): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => 402,
                'type' => 'INSUFFICIENT_BALANCE',
                'message' => $message,
            ],
        ], 402);
    }

    public function monitorOrderStatus($orderID, $maxAttempts = 30, $pollInterval = 10)
    {
        $attempts = 0;
        $order = KGenOrder::find($orderID);

        if (! $order) {
            throw new \Exception("Order not found: {$orderID}");
        }

        while ($attempts < $maxAttempts) {
            $attempts++;
            Log::info("Order {$orderID} status: {$order->status}/{$order->fulfillment_status}");

            if ($order->status === 'COMPLETED' && $order->fulfillment_status === 'FULFILLED') {
                return $order;
            }

            if (in_array($order->status, ['FAILED', 'CANCELLED'])) {
                throw new \Exception("Order {$order->status}: {$order->fulfillment_status}");
            }

            sleep($pollInterval);
        }

        throw new \Exception("Order monitoring timeout for Order ID: {$orderID}");
    }

    public function showSuccess($orderId)
    {
        $order = KGenOrder::findOrFail($orderId);

        Log::info('KGen order success view', [
            'order_id' => $orderId,
            'has_api_response' => (bool) $order->api_response,
            'voucher_code' => $order->voucher_code,
        ]);

        return Inertia::render('Storefront/KGen/OrderSuccess', [
            'order' => $order->only(['id', 'external_ref', 'status', 'payable_amount', 'variant_id']),
        ]);
    }

    public function showFailed($orderId)
    {
        $order = KGenOrder::findOrFail($orderId);

        return Inertia::render('Storefront/KGen/OrderFailed', [
            'order' => $order->only(['id', 'external_ref', 'status', 'payable_amount', 'variant_id']),
        ]);
    }

    // Keep your existing methods as-is (fetchProducts, sendTransactionMail, etc.)
    public function fetchProducts()
    {
        $response = Http::withHeaders([
            'x-client-id' => env('EXLR8_USER_ID'),
            'x-client-secret' => env('EXLR8_USER_SECRET'),
        ])->get(env('EXLR8_BASE_URL').'/products/delivery-partners/'.env('dpID'));

        if ($response->successful()) {
            $products = $response->json()['products'] ?? [];
            \Log::info('Fetched products', ['count' => count($products)]);

            return $products;
        }

        \Log::error('Failed to fetch products', ['status' => $response->status()]);

        return [];
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\KGenOrder;
use App\Models\KgenProduct;
use App\Jobs\MonitorOrderStatus;

class KGenOrderController extends Controller
{
    /**
     * Monitor an order status with polling
     *
     * @param int|string $orderID
     * @param int $maxAttempts
     * @param int $pollInterval (seconds)
     * @return KGenOrder
     * @throws Exception
     */

    public function store(Request $request)
    {
        if (!$request->has('variant_id')) {
            return kgenError("invalid variant ID", "BAD_REQUEST");
        }

        if (!auth()->check()) {
            return kgenError("unauthenticated", "UNAUTHORIZED");
        }

         if (!auth()->user()->hasRole('admin')) {
            return kgenError(
                "Insufficient permissions: You don’t have access to this resource",
                "FORBIDDEN"
            );
        }

            if(env('dpID') == '')
        {
        return kgenError("forbidden: param: admin user does not have access to DP: INVALID_DP_ID", "FORBIDDEN");
        }

        $product = KgenProduct::latest()->first();

        if (!$product) {
            return kgenError(
                "Record not found",
                "RECORD_NOT_FOUND",
                ["reason" => "No matching record", "id" => $id]
            );
        }
    }

    public function showForm(Request $request)
    {
        return view('kgen.place-order', [
            'variantId' => $request->variantId,
            'mrp' => $request->mrp,
        ]);
    }

    public function placeOrder(Request $request)
    {
       $validated = $request->validate([
        'variantId' => 'required|string',
        ]);

           $dpValue = env('dpID');
            $balanceResponse = Http::withHeaders([
            'x-client-id'     => env('EXLR8_USER_ID'),
            'x-client-secret' => env('EXLR8_USER_SECRET'),
        ])->get(env('EXLR8_BASE_URL') . '/delivery-partners/' . $dpValue . '/wallet/balance');

        if (!$balanceResponse->successful()) {
            return back()->withErrors([
                'error' => 'Unable to check wallet balance at the moment. Please try again later.',
            ]);
        }

        $balanceData = $balanceResponse->json();
        $availableBalance = $balanceData['balance'] ?? 0;

         $priceResponse = Http::withHeaders([
            'x-client-id' => env('EXLR8_USER_ID'),
            'x-client-secret' => env('EXLR8_USER_SECRET'),
        ])->get(env('EXLR8_BASE_URL') . '/products/delivery-partners/'. env('dpID'));

           // dd($priceResponse->json());
        if (!$priceResponse->successful()) {
            return back()->withErrors([
                'error' => 'Unable to fetch product price.',
            ]);
        }

        $priceData = $priceResponse->json();
        $productPrice = $priceData['variants']['price'] ?? 0;

        if ($availableBalance < $productPrice) {
            return back()->withErrors([
                'error' => "Insufficient balance. Your wallet has {$availableBalance}, but the product costs {$productPrice}.",
            ]);
        }

        $validated = $request->validate([
            'variantId' => 'required|string',
        ]);
        

        $dpValue = env('dpID');
        $externalRefID = 'ORDER_' . strtoupper(Str::random(6));

        $response = Http::withHeaders([
            'x-client-id' => env('EXLR8_USER_ID'),
            'x-client-secret' => env('EXLR8_USER_SECRET'),
            'Content-Type' => 'application/json',
        ])->post(env('EXLR8_BASE_URL') . '/orders/b2b/direct-checkout', [
            'dpID' => $dpValue,
            'variantID' => $validated['variantId'],
            'externalRefID' => $externalRefID, // Unique ID
        ]);

        //dd($response->json());

       /*if ($response->successful()) {
             return back()->with('success', 'Order placed successfully');
        }

        if (!$response->successful()) {
        return back()->withErrors(['error' => 'Failed to place order']);
        }*/
        
         $data = $response->json();

    // Handle response based on status
    switch ($data['status'] ?? null) {
        case 'COMPLETED':
            // recharge successful — show voucher details if available
            $orderID = $data['orderID'];
            $vouchers = $data['lineItems'][0]['vouchers'] ?? [];
            $voucherdata =json_encode($vouchers);

             KGenOrder::create([
                'variant_id'   => $validated['variantId'],
                'external_ref' => $externalRefID,
                'mrp'          => $productPrice,
                'api_response' => json_encode($data),
            ]);
          // return back()->with('success', "Order placed successfully. Order ID: {$orderID} . Vouchers: " . json_encode($vouchers));
            return back()->with([
    'success'  => 'Order placed successfully',
    'orderID'  => $orderID,
    'vouchers' => $vouchers,
    ]);
    
        case 'PROCESSED':
            // recharge is still in process
           /* return redirect()->route('recharges')->with([
                'info' => 'Order is being processed. Please check status later.',
                'orderId' => $data['orderID'],
            ]);*/
             KGenOrder::create([
                'variant_id'   => $validated['variantId'],
                'external_ref' => $externalRefID,
                'mrp'          => $productPrice,
                'api_response' => json_encode($data),
            ]);
              return back()->with('success', 'Order placed successfully');

        case 'FAILED':
            // recharge failed
            return back()->withErrors([
                'error' => 'Order failed. Please try again later.',
                'orderId' => $data['orderID'] ?? null,
            ])->withInput();

        default:
            // unknown status
            return back()->withErrors([
                'error' => 'Unexpected response from API.',
            ])->withInput();
        }
    }

    /**
     * Handle order status actions
     *
     * @param Order $order
     * @return void
     */
    function handleOrderStatus(Order $order): void
    {
        switch ($order->status) {
            case 'PROCESSING':
                Log::info('Order is pending confirmation');
                // TODO: Show pending status to user (e.g., update DB flag or broadcast event)
                break;

            case 'CONFIRMED':
                Log::info('Order confirmed, processing will begin');
                // TODO: Update user interface (e.g., broadcast event or notification)
                break;

            case 'PROCESSING':
                Log::info('Order is being processed');
                // TODO: Continue monitoring (could re-dispatch MonitorOrderStatus job)
                break;

            case 'COMPLETED':
                if ($order->fulfillment_status === 'FULFILLED') {
                    Log::info('Order completed successfully');
                    downloadOrderAssets($order);
                    notifyCustomer($order);
                } else {
                    Log::warning('Order completed but fulfillment failed');
                    handleFulfillmentFailure($order);
                }
                break;

            case 'FAILED':
                Log::error('Order failed');
                handleOrderFailure($order);
                break;

            case 'CANCELLED':
                Log::warning('Order was cancelled');
                handleOrderCancellation($order);
                break;

            default:
                Log::info("Order has unknown status: {$order->status}");
                break;
        }
    }

   /* public function placeMobileRechargeOrder(Request $request)
{
    $validated = $request->validate([
        'variantId' => 'required|string',
        'mobileNumber' => 'required|digits_between:10,15', // validate number length
    ]);

    $response = Http::withHeaders([
        'x-client-id' => env('EXLR8_USER_ID'),
        'x-client-secret' => env('EXLR8_USER_SECRET'),
        'Content-Type' => 'application/json',
    ])->post(env('EXLR8_BASE_URL') . '/orders/b2b/direct-checkout', [
        'variantID'    => $validated['variantId'],   // note: capital "ID"
        'externalRef'  => 'RECHARGE_' . strtoupper(Str::random(8)), // Unique ID
        'mobileNumber' => $validated['mobileNumber'],
    ]);

    if ($response->successful()) {
        return redirect()->route('recharges')->with('success', 'Mobile recharge placed successfully!');
    }

     if (!$response->successful()) {
    return back()->withErrors(['error' => 'Failed to place mobile recharge'])->withInput();
     }

      $data = $response->json();

    // Handle response based on status
    switch ($data['status'] ?? null) {
        case 'COMPLETED':
            // recharge successful — show voucher details if available
            $vouchers = $data['lineItems'][0]['vouchers'] ?? [];
            return redirect()->route('recharges')->with([
                'success' => 'Recharge completed successfully!',
                'orderId' => $data['orderID'],
                'vouchers' => $vouchers,
            ]);

        case 'PROCESSED':
            // recharge is still in process
            return redirect()->route('recharges')->with([
                'info' => 'Recharge is being processed. Please check status later.',
                'orderId' => $data['orderID'],
            ]);

        case 'FAILED':
            // recharge failed
            return back()->withErrors([
                'error' => 'Recharge failed. Please try again later.',
                'orderId' => $data['orderID'] ?? null,
            ])->withInput();

        default:
            // unknown status
            return back()->withErrors([
                'error' => 'Unexpected response from recharge API.',
            ])->withInput();
    }
}*/


    public function listOrders()
    {
    $orders = \App\Models\KGenOrder::latest()->paginate(10);
    return view('kgen.orders', compact('orders'));
    }

    public function getOrders(Request $request)
    {
        // Build query params based on rules
        $queryParams = [];

        if ($request->filled('externalRef')) {
            $queryParams['externalRef'] = $request->externalRef;
        } elseif ($request->filled('orderId')) {
            $queryParams['orderId'] = $request->orderId;
        } elseif ($request->filled('pagination')){
            $queryParams['nextCursor'] = $request->nextCursor;
            $queryParams['limit'] = $request->limit ?? 10;
        }

        $response = Http::withHeaders([
            'x-client-id' => env('EXLR8_USER_ID'),
            'x-client-secret' => env('EXLR8_USER_SECRET'),
        ])->get(env('EXLR8_BASE_URL') . '/orders/b2b/delivery-partners/{dpID}', $queryParams);

        if ($response->successful()) {
            // Single order fetch
            if (isset($response['order'])) {
                return view('order-detail', [
                    'order' => $response['order']
                ]);
            }

            // Multiple orders fetch
            return view('orders-list', [
                'orders' => $response['orders'] ?? [],
            ]);
        }

        return back()->withErrors(['error' => 'Failed to fetch orders']);
    }

      public function getOrders2(Request $request)
    {
        // Build query params based on rules
        $queryParams = [];
        if ($request->filled('orderId')) {
            $queryParams['orderId'] = $request->orderId;
        }
        $response = Http::withHeaders([
            'x-client-id' => env('EXLR8_USER_ID'),
            'x-client-secret' => env('EXLR8_USER_SECRET'),
        ])->get(env('EXLR8_BASE_URL') . '/orders/b2b/delivery-partners/{dpID}/{orderID} ', $queryParams);
        if ($response->successful()) {
            // Single order fetch
            if (isset($response['order'])) {
                return view('order-detail', [
                    'order' => $response['order']
                ]);
            }

            // Multiple orders fetch
            return view('orders-list', [
                'orders' => $response['orders'] ?? [],
            ]);
        }
        return back()->withErrors(['error' => 'Failed to fetch orders']);
    }

    public function getOrders3(Request $request)
    {
        // Build query params based on rules
        $queryParams = [];
        if ($request->filled('externalRef')) {
            $queryParams['externalRef'] = $request->externalRef;
        }
        $response = Http::withHeaders([
            'x-client-id' => env('EXLR8_USER_ID'),
            'x-client-secret' => env('EXLR8_USER_SECRET'),
        ])->get(env('EXLR8_BASE_URL') . '/orders/b2b/delivery-partners/{dpID}/external-ref/{externalRef} ', $queryParams);
        if ($response->successful()) {
            // Single order fetch
            if (isset($response['order'])) {
                return view('order-detail', [
                    'order' => $response['order']
                ]);
            }

            // Multiple orders fetch
            return view('orders-list', [
                'orders' => $response['orders'] ?? [],
            ]);
        }
        return back()->withErrors(['error' => 'Failed to fetch orders']);
    }

    public function insufficientBalance($message = "The purchase could not be completed due to insufficient balance."): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => 402,
                'type' => 'INSUFFICIENT_BALANCE',
                'message' => $message,
            ]
        ], 402);
    }

     function monitorOrderStatus($orderID, $maxAttempts = 30, $pollInterval = 10)
    {
        $attempts = 0;

        while ($attempts < $maxAttempts) {
            $attempts++;

            // Fetch order (adjust depending on your DB/model)
            $order = KGenOrder::find($orderID);
           // MonitorOrderStatus::dispatch($orderID);

            if (! $order) {
                throw new Exception("Order not found: {$orderID}");
            }

            Log::info("Order {$orderID} status: {$order->status}/{$order->fulfillment_status}");

            // ✅ Terminal success state
            if ($order->status === "COMPLETED" && $order->fulfillment_status === "FULFILLED") {
                return $order;
            }

            // ❌ Terminal failure states
            if (in_array($order->status, ["FAILED", "CANCELLED"])) {
                throw new Exception("Order {$order->status}: {$order->fulfillment_status}");
            }

            //if order status is PROCESSING/CONFIRMED, run getorders API
            if($order->status === "PROCESSING" OR $order->status === "CONFIRMED")
            {
                $response = Http::withHeaders([
            'x-client-id' => env('EXLR8_USER_ID'),
            'x-client-secret' => env('EXLR8_USER_SECRET'),
            ])->get(env('EXLR8_BASE_URL') . '/orders/b2b/delivery-partners/{dpID}');
            if ($response->successful()) {
            // Single order fetch
                if (isset($response['order'])) {
                    return view('order-detail', [
                        'order' => $response['order']
                    ]);
            }

            // Multiple orders fetch
            return view('orders-list', [
                'orders' => $response['orders'] ?? [],
            ]);
                
            }

            // Wait before retrying
            sleep($pollInterval);
        }

        throw new Exception("Order monitoring timeout for Order ID: {$orderID}");
    } 
}

    public function downloadOrderAssets(KGenOrder $order): ?array
    {
        if ($order->fulfillment_status !== 'FULFILLED' || empty($order->asset_url)) {
            return null;
        }

        try {
            // Download the asset file
            $fileContents = file_get_contents($order->asset_url);

            if ($fileContents === false) {
                throw new \Exception("Failed to download asset from {$order->asset_url}");
            }

            // Generate a filename (you can customize this)
            $filename = "orders/{$order->id}/asset_" . time() . ".bin";

            // Save file to storage/app/public/orders/... (adjust disk as needed)
            Storage::disk('public')->put($filename, $fileContents);

            $assetData = [
                'file_path' => $filename,
                'password' => $order->file_password,
                'orderID' => $order->id,
                'downloaded_at' => now()->toISOString(),
            ];

            // Process vouchers from line items
            $vouchers = [];
            foreach ($order->lineItems as $item) {
                if (!empty($item->vouchers)) {
                    $vouchers = array_merge($vouchers, $item->vouchers);
                }
            }

            return [
                'assetData' => $assetData,
                'vouchers' => $vouchers,
                'orderDetails' => $order,
            ];
        } catch (\Exception $e) {
            Log::error("Error downloading order assets for Order {$order->id}: {$e->getMessage()}");
            throw $e;
        }
    }

    // Ensure this method is inside the class
    public function showAssets(KGenOrder $order)
    {
        $assetData = $this->downloadOrderAssets($order); // Call the helper function

        if (!$assetData) {
            return view('kgen.order.assets')->with('message', 'No assets available for this order.');
        }

        return view('kgen.order.assets', [
            'order' => $order,
            'assetData' => $assetData['assetData'],
            'vouchers' => $assetData['vouchers'],
        ]);
    }

    public function fetchProducts()
    {
        $response = Http::withHeaders([
            'x-client-id' => env('EXLR8_USER_ID'),
            'x-client-secret' => env('EXLR8_USER_SECRET'),
        ])->get(env('EXLR8_BASE_URL') . '/products/delivery-partners/' . env('dpID'));

        if ($response->successful()) {
            // Extract products array from response
            $products = $response['products'] ?? [];

            // Optional: log or debug
            \Log::info('Fetched products', ['count' => count($products)]);

            return $products;
        }

        // Handle failure
        \Log::error('Failed to fetch products', ['status' => $response->status()]);
        return [];
    }

    public function sendTransactionMail($prepareMailDetails)
    {
        $recipientEmail = $prepareMailDetails["billing_email"] ?? null;
        $recipientName = $prepareMailDetails["billing_name"] ?? null;

        if (empty($recipientEmail) || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            Log::error("Transaction mail not sent: invalid or empty recipient email", [
                'recipient_email' => $recipientEmail,
                'recipient_name' => $recipientName,
                'order_id' => $prepareMailDetails["order_id"] ?? 'N/A',
                'prepare_mail_details' => $prepareMailDetails
            ]);
            return;
        }

        try {
            Log::info("Attempting to send transaction mail", [
                'recipient_email' => $recipientEmail,
                'recipient_name' => $recipientName,
                'order_id' => $prepareMailDetails["order_id"] ?? 'N/A'
            ]);

            // Generate invoice PDF
            $pdf = PDF::loadView("layouts.invoice", $prepareMailDetails);

            // Send mail with product details
            Mail::send("layouts.mail", [
                "prepareMailDetails" => $prepareMailDetails,
                "pdf" => $pdf
            ], function ($message) use ($prepareMailDetails, $pdf) {
                $message->from(config("companyDefaultValues.sendMailFrom"), config("companyDefaultValues.company_name"))
                    ->to($prepareMailDetails["billing_email"], $prepareMailDetails["billing_name"])
                    ->subject(config("companyDefaultValues.default_subject"))
                    ->attachData($pdf->output(), "invoice.pdf");
            });

            Log::info("Transaction mail sent successfully", [
                'recipient_email' => $recipientEmail,
                'recipient_name' => $recipientName,
                'order_id' => $prepareMailDetails["order_id"] ?? 'N/A'
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to send transaction mail", [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'recipient_email' => $recipientEmail,
                'recipient_name' => $recipientName,
                'order_id' => $prepareMailDetails["order_id"] ?? 'N/A',
                'stack_trace' => $e->getTraceAsString()
            ]);
        }
    }

    public function sendTransactionMailWithProducts($billingEmail, $billingName)
    {
        $products = $this->fetchProducts(); // fetch product details from API

        $prepareMailDetails = [
            'billing_email' => $billingEmail,
            'billing_name' => $billingName,
            'order_id' => 'ORD-' . now()->timestamp,
            'products' => $products,
        ];

        $this->sendTransactionMail($prepareMailDetails);
    }



}

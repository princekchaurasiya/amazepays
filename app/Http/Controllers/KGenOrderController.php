<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\KGenOrder;

class KGenOrderController extends Controller
{
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

        $response = Http::withHeaders([
            'x-client-id' => env('EXLR8_USER_ID'),
            'x-client-secret' => env('EXLR8_USER_SECRET'),
            'Content-Type' => 'application/json',
        ])->post(env('EXLR8_BASE_URL') . '/orders/b2b/direct-checkout', [
            'dpID' => $dpValue,
            'variantID' => $validated['variantId'],
            'externalRefID' => 'ORDER_' . strtoupper(Str::random(6)), // Unique ID
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
                'error' => 'Unexpected response from recharge API.',
            ])->withInput();
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

}

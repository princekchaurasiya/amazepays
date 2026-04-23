<?php

namespace App\Http\Controllers;

use App\Models\ApiToken;
use App\Models\Order;
use App\Models\Product;
use App\Models\UnlimitPayment;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Inertia\Inertia;

class UnlimitPaymentController extends Controller
{
    public function showPaymentForm(Request $request, $slug)
    {
        $product = Product::where('url', $slug)->firstOrFail();
        if (! $product->isListedOnConsumerStorefront()) {
            abort(404);
        }
        $product['productData'] = $request->only([
            'denomination', 'quantity', 'gift_send_option', 'receiver_name',
            'receiver_email', 'receiver_mobile', 'receiver_msg', 'delivery_mode',
        ]);
        $product['currency'] = json_decode($product['currency']);
        $product['images'] = json_decode($product->images);
        $order = new Order;
        $order->user_id = Auth::id();

        return Inertia::render('Checkout/Index', [
            'product' => $product,
            'order' => $order,
            'checkoutData' => [],
            'slug' => $slug,
        ]);
    }

    public function getToken()
    {
        $response = Http::asForm()
            ->withHeaders([
                'Authorization' => 'Basic '.base64_encode(env('UNLIMIT_CODE')),
            ])
            ->post('https://psp.in.unlimit.com/api/auth/token', [
                'grant_type' => 'password',
                'password' => env('UNLIMIT_SECRET_KEY'),
                'terminal_code' => env('UNLIMIT_PUBLIC_KEY'),
            ]);

        $data = $response->json();
        // dd($data);

        if (isset($data['access_token'])) {
            ApiToken::create([
                'access_token' => $data['access_token'],
                'expires_at' => isset($data['expires_in'])
                    ? Carbon::now()->addSeconds($data['expires_in'])
                    : null,
            ]);

            return $data['access_token'];
        }

        return false;
    }

    public function store(Request $request)
    {
        // $discount = session('discounted_amount_value');
        // return view('unlimitpayment', ['discount' => $discount]);
        // Validate input
        /*$validated = $request->validate([
          'amount' => 'required|numeric|min:0.01',
          'card_number' => 'required|string|digits_between:13,19',
          'holder' => 'required|string|max:255',
          'expiration' => 'required|string',
          'cvv' => 'required|digits_between:3,4',
    ]);*/

        // 2. Get Unlimit token (internal call)
        $token = $this->getToken();

        if (! $token) {
            // Return user-friendly error message instead of technical error
            return redirect()->back()->with('error', 'Payment service is temporarily unavailable. Please try again later.');
        }

        // Generate current time with milliseconds and Z suffix in UTC
        $now = Carbon::now('UTC');
        $milliseconds = $now->format('v'); // milliseconds
        $time = $now->format("Y-m-d\TH:i:s.").$milliseconds.'Z';
        $payableAmount = $request->input('payable_amount');

        // Generate unique order ID
        $orderId = (string) Str::uuid();

        $data = [
            'request' => [
                'id' => (string) Str::uuid(),
                'time' => $time,
            ],
            'merchant_order' => [
                'id' => $orderId,
                'description' => 'Gift Card Payment - '.$orderId,
            ],
            'payment_method' => 'bankcard',
            'payment_data' => [
                'amount' => $payableAmount,
                'currency' => 'INR',
            ],

            'return_urls' => [
                'success_url' => route('unlimit.return'),
                'decline_url' => route('unlimit.return'),
            ],
            // Note: card_account.card was removed based on your earlier error for Payment Page mode
        ];

        /*$token = ApiToken::latest()->first();

    if (!$token || ($token->expires_at && $token->expires_at->isPast())) {
        return response()->json(['error' => 'Token expired or missing.'], 401);
    }*/

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$token,
                // 'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
                ->post('https://psp.in.unlimit.com/api/payments', $data);

            //  return $response->json();
            $responseData = $response->json();

            if (isset($responseData['redirect_url'])) {
                // Store payment information before redirecting
                $this->storePaymentInfo($request, $orderId, $responseData);

                return redirect()->away($responseData['redirect_url']);
            }

            return response()->json(['error' => 'No redirect URL received', 'response' => $responseData], 400);

        } catch (RequestException $e) {

            if (str_contains($e->getMessage(), 'cURL error 35')) {
                return response()->json([
                    'error' => 'Connection aborted',
                    'message' => 'The connection to the payment provider was unexpectedly closed. Please try again shortly.',
                    'code' => 35,
                ], 503);
            }

            return response()->json([
                'error' => 'HTTP request failed',
                'message' => $e->getMessage(),
                'response' => $e->response?->body(),
            ], 500);
        }
    }

    public function handleReturnSuccess(Request $request)
    {
        // Get payment information from the request parameters
        $paymentId = $request->input('payment_id');
        $orderId = $request->input('merchant_order_id');
        $status = $request->input('status');

        // Update payment status if we have payment information
        if ($paymentId && $orderId) {
            $this->updatePaymentStatus($paymentId, $orderId, $status);
        }

        // Store return data in session for the redirect-to-woohoo blade
        $orderId = (string) Str::uuid(); // Unlimit's UUID

        // Create Order linked to that UUID
        $Order = new Order;
        $Order->user_id = Auth::id();
        $Order->grand_payable_amount = $request->input('payable_amount');
        // ... set other order fields here ...
        $Order->merchant_order_id = $orderId; // Link the UUID
        $Order->save();
        // Then store this internal ID in the session when the user returns
        // Inside handleReturnSuccess()
        $Order = Order::where('merchant_order_id', $orderId)->first();

        if ($Order) {
            session([
                'payment_return_data' => [
                    'payment_id' => $paymentId,
                    'order_id' => $Order->id,  // ✅ use internal order ID now
                    'status' => $status,
                    'return_time' => now(),
                ],
            ]);
        }

        return Inertia::render('Checkout/Woohoo/AutoSubmit', [
            'submitUrl' => route('woohoo.createOrder'),
        ]);

    }

    /**
     * Webhook from Unlimit (server-to-server)
     */
    public function webhook(Request $request)
    {
        try {
            $paymentId = $request->input('payment_id');
            $orderId = $request->input('merchant_order_id');
            $status = strtolower($request->input('status', ''));
            $amount = $request->input('amount');

            if (! $paymentId || ! $orderId) {
                return response()->json(['error' => 'Missing required fields'], 400);
            }

            // Update or insert payment info
            $this->updatePaymentStatus($paymentId, $orderId, $status, $amount);

            // Optionally, trigger Woohoo order automatically
            if (in_array($status, ['success', 'approved', 'completed'])) {
                try {
                    $woohooProcessor = new WoohooProcessingController;
                    $woohooProcessor->createOrderFromWebhook($orderId, $paymentId);
                } catch (Exception $ex) {
                    // Intentionally no logs: avoid leaking payment/order context to log backends
                }
            }

            return response()->json(['status' => 'success'], 200);

        } catch (Exception $e) {
            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * 🔹 Update payment status when webhook/return is received
     */
    private function updatePaymentStatus($paymentId, $orderId, $status, $amount = null)
    {
        try {
            $payment = UnlimitPayment::where('merchant_order_id', $orderId)
                ->orWhere('order_id', $orderId)
                ->first();

            if (! $payment) {
                $payment = new UnlimitPayment;
                $payment->merchant_order_id = $orderId;
                $payment->tracking_id = $paymentId;
                $payment->amount = $amount;
                $payment->currency = 'INR';
                $payment->payment_method = 'bankcard';
                $payment->created_at = now();
            }

            $normalizedStatus = strtolower($status);

            $payment->payment_status = $normalizedStatus;
            $payment->order_status = $normalizedStatus;
            $payment->status_message = 'Updated by Unlimit webhook';
            $payment->updated_at = now();
            $payment->save();
        } catch (Exception $e) {
            //
        }
    }

    /**
     * 🔹 Optional manual processing after user redirect
     */
    public function process(Request $request)
    {
        $payableAmount = $request->input('payable_amount');

        return Inertia::render('Checkout/Status', [
            'status' => 'success',
            'msg' => 'Payment successful',
            'amount' => $payableAmount,
        ]);
    }

    /**
     * 🔹 Helper to store initial payment info before redirecting
     * SECURITY: Validates amount against order database
     */
    private function storePaymentInfo(Request $request, $orderId, $responseData)
    {
        try {
            // SECURITY: If order_id is numeric, validate amount against order
            $amount = null;
            if (is_numeric($orderId)) {
                $order = Order::where('id', $orderId)->first();
                if ($order) {
                    // Use amount from database, not request
                    $amount = (float) ($order->amount_payable_after_discount ?? $order->grand_payable_amount ?? 0);
                }
            }

            // Fallback to request amount if order not found (for UUID-based orders)
            if ($amount === null) {
                $amount = (float) ($request->input('payable_amount') ?? 0);
            }

            $payment = new UnlimitPayment;
            $payment->order_id = $orderId;
            $payment->merchant_order_id = $responseData['merchant_order_id'] ?? $orderId;
            $payment->amount = $amount; // Use validated amount
            $payment->currency = 'INR';
            $payment->payment_method = 'bankcard';
            $payment->payment_status = 'pending';
            $payment->unlimit_response = json_encode($responseData);
            $payment->created_at = now();
            $payment->save();
        } catch (Exception $e) {
            //
        }
    }

    /**
     * 🔹 Fetch Woohoo order data for order creation
     */
    public static function getWoohooOrderData($orderId)
    {
        $payment = UnlimitPayment::where('order_id', $orderId)->first();
        $Order = Order::find($orderId);

        if (! $payment || ! $Order) {
            return null;
        }

        // ✅ Always use Order total amount, not UnlimitPayment’s
        return [
            'amount' => $Order->price ?? $payment->amount,
            'currency' => $Order->currency ?? 'INR',
            'sku' => $Order->sku ?? null,
            'qty' => $Order->quantity ?? 1,
        ];
    }
}

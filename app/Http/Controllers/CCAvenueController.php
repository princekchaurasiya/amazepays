<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderSummary;
use App\Models\User;
use Auth;
use Config;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class CCAvenueController extends Controller
{
    protected $cryptoController;

    protected $isSuccessful = false;

    public function __construct(CryptoController $cryptoController)
    {
        $this->cryptoController = $cryptoController;
    }

    public function processPayment(Request $request)
    {
        try {
            // Check if user is blocked from transactions
            if (Auth::check()) {
                $user = Auth::user();
                if (! $user->can_transact) {
                    Log::warning('User blocked from transactions attempted payment', [
                        'user_id' => $user->id,
                        'mobile' => $user->mobile,
                    ]);

                    return Inertia::render('Error', [
                        'status' => 403,
                        'message' => $user->restriction_reason ?: 'Your account cannot process payments at this time.',
                    ])->toResponse($request)->setStatusCode(403);
                }

                // Check for restricted features
                if ($user->restricted_features) {
                    $restrictedFeatures = json_decode($user->restricted_features, true);
                    if (in_array('payment', $restrictedFeatures)) {
                        Log::warning('User with restricted features attempted payment', [
                            'user_id' => $user->id,
                            'mobile' => $user->mobile,
                            'restricted_features' => $restrictedFeatures,
                        ]);

                        return Inertia::render('Error', [
                            'status' => 403,
                            'message' => $user->restriction_reason ?: 'Your account cannot process payments at this time.',
                        ])->toResponse($request)->setStatusCode(403);
                    }
                }
            }

            // Define custom validation messages
            $messages = [
                'billing_name.required' => 'Please enter a name.',
                'billing_name.regex' => 'Name can only contain letters and spaces.',
                'billing_name.max' => 'Name cannot exceed 30 characters.',
                'billing_email.required' => 'Please enter an email address.',
                'billing_email.email' => 'Please enter a valid email address.',
                'billing_email.max' => 'Email cannot exceed 50 characters.',
                'billing_tel.required' => 'Please enter a phone number.',
                'billing_tel.digits' => 'Phone number must be 10 digits.',
                'billing_tel.regex' => 'Phone number must be a valid Indian number.',
                'billing_zip.required' => 'Please enter a zip code.',
                'billing_zip.digits' => 'Zip code must be 6 digits.',
                'billing_zip.regex' => 'Zip code must be a valid numeric code.',
                'billing_address.required' => 'Please enter your address.',
                'billing_address.max' => 'Address cannot exceed 50 characters.',
                'billing_address_two.required' => 'Please enter your second address.',
                'billing_address_two.max' => 'Address cannot exceed 50 characters.',
                'billing_city.required' => 'Please enter your city.',
                'billing_city.max' => 'City cannot exceed 40 characters.',
                'billing_state.required' => 'Please enter your state.',
                'billing_state.max' => 'State cannot exceed 40 characters.',
                'billing_country.required' => 'Please enter your country.',
                'billing_country.max' => 'Country cannot exceed 40 characters.',
                'billing_gst_number.max' => 'GST number cannot exceed 15 characters.',
            ];

            // Validate the form data
            $request->validate([
                'billing_name' => 'required|regex:/^[a-zA-Z\s]+$/|max:30',
                'billing_email' => 'required|email|max:50',
                'billing_tel' => ['required', 'digits:10', 'regex:/^[0-9]{10}$/'],
                'billing_zip' => ['required', 'digits:6', 'regex:/^[0-9]{6}$/'],
                'billing_address' => 'required|max:50',
                'billing_address_two' => 'required|max:50',
                'billing_city' => 'required|max:40',
                'billing_state' => 'required|max:40',
                'billing_country' => 'required|max:40',
                'billing_gst_number' => 'nullable|max:15',
            ], $messages);

            // SECURITY: Log minimal info (no sensitive data like passwords, tokens)
            Log::info('Process payment request', [
                'user_id' => Auth::id(),
                'order_id' => session('checkout_order_id'),
                'ip_address' => $request->ip(),
            ]);

            // Update user details
            $user = Auth::user();
            $user->update([
                // 'name' => $request->input('billing_name'),
                // 'email' => $request->input('billing_email'),
                // 'mobile' => $request->input('billing_tel'),
                'billing_zip' => $request->input('billing_zip'),
                'billing_address' => $request->input('billing_address'),
                'billing_address_two' => $request->input('billing_address_two'),
                'billing_city' => $request->input('billing_city'),
                'billing_state' => $request->input('billing_state'),
                'billing_country' => $request->input('billing_country'),
            ]);

            // Handle payment processing
            $sessionId = session('checkout_order_id');
            if (! $this->updateOrderFromCcAvenue($sessionId, $request)) {
                Log::error('Record not found for session ID: '.$sessionId);

                return Inertia::render('Checkout/OrderStatus', [
                    'errorMessage' => 'Record not found for session ID',
                    'isSuccessful' => false,
                ]);
            }

            $paymentData = $this->preparePaymentData($sessionId, $request);
            $encryptedData = $this->encryptPaymentData($paymentData);
            $accessCode = config('paymentconfig.access_code');
            $ccavenueApiEndpoint = config('paymentconfig.ccavenue_api_endpoint');

            return Inertia::render('Checkout/CcAvenueRedirect', [
                'action' => $ccavenueApiEndpoint,
                'encRequest' => $encryptedData,
                'accessCode' => $accessCode,
            ]);

        } catch (ValidationException $e) {
            // Handle validation exceptions
            Log::error('Validation failed', [
                'errors' => $e->errors(),
                'message' => $e->getMessage(),
            ]);

            return redirect()->back()->withErrors($e->errors())->withInput();

        } catch (Exception $e) {
            // Handle general exceptions
            Log::error('Payment processing failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Inertia::render('Checkout/OrderStatus', [
                'errorMessage' => 'An error occurred while processing your payment.',
                'isSuccessful' => false,
            ]);
        }
    }

    // protected function preparePaymentData($sessionId, Request $request)
    // {

    //     $paymentData = $request->all();

    //     $paymentData['order_id'] = $sessionId;
    //     $paymentData['denomination'] = session('denomination');
    //     $paymentData['amount'] = session('total_payable_amount_after_discount');
    //     $paymentData['quantity'] = session('quantity');
    //     $paymentData['numericCode'] = config('paymentconfig.numeric_code', '356');
    //     $paymentData['currency'] = config('paymentconfig.currency', 'INR');
    //     $paymentData['language'] = config('paymentconfig.language', 'EN');
    //     $paymentData['redirect_url'] = route('response_ccavenue');
    //     $paymentData['cancel_url'] = url('payment-cancel');
    //     $paymentData['merchant_id'] = config('paymentconfig.merchant_id');
    //     return $paymentData;
    // }

    protected function preparePaymentData($sessionId, Request $request)
    {
        $paymentData = $request->only([
            'billing_name', 'billing_email', 'billing_tel', 'billing_zip',
            'billing_address', 'billing_address_two', 'billing_city', 'billing_state',
            'billing_country', 'billing_gst_number',
        ]);

        // Grouping session variables
        $paymentData['order_id'] = $sessionId;

        $orderData = Order::where('id', $sessionId)->first();
        // dd($orderData);

        $paymentData['denomination'] = $orderData->denomination;
        $paymentData['quantity'] = $orderData->quantity;
        $paymentData['amount'] = $orderData->amount_payable_after_discount;

        // Payment configuration
        $paymentData['numericCode'] = config('paymentconfig.numeric_code', '356');
        $paymentData['currency'] = config('paymentconfig.currency', 'INR');
        $paymentData['language'] = config('paymentconfig.language', 'EN');
        $paymentData['merchant_id'] = config('paymentconfig.merchant_id');

        // URLs
        $paymentData['redirect_url'] = route('response_ccavenue');
        $paymentData['cancel_url'] = url('payment-cancel');

        return $paymentData;
    }

    protected function encryptPaymentData($paymentData)
    {
        $merchantData = http_build_query($paymentData);
        $workingKey = config('paymentconfig.working_key');

        return $this->cryptoController->encryptCCAvenue($merchantData, $workingKey);
    }

    public function handlePaymentCancellation(Request $request)
    {

        try {
            $Order = Order::where('id', $request->orderNo)->with('product')->firstOrFail();
            $productSlug = $Order->product->slug;
            $Order->update(['order_status' => 'Cancelled']);
            Log::info("Order ID {$Order->id} status updated to Cancelled");

            // return redirect()->route('checkoutPage', ['slug' => $productSlug])->with('error-message', 'Payment was cancelled. Please try again.');
            return Inertia::render('Checkout/OrderStatus', [
                'transactionStatusMessage' => 'Payment was cancelled. Please try again.',
                'isSuccessful' => false,
            ]);
        } catch (Exception $e) {
            Log::error("Error handling payment cancellation: {$e->getMessage()}");

            return Inertia::render('Checkout/OrderStatus', [
                'transactionStatusMessage' => 'An error occurred while processing your payment.',
                'isSuccessful' => false,
            ]);
        }
    }

    public function responseCcavenue(Request $request)
    {
        try {
            $workingKey = config('paymentconfig.working_key');
            $encResponse = $request->input('encResp');
            $rcvdString = $this->cryptoController->decryptCCAvenue($encResponse, $workingKey);
            $decryptValues = explode('&', $rcvdString);
            $ccAvenueCollectedDataArray = [];
            $order_status = '';
            foreach ($decryptValues as $value) {
                [$key, $val] = explode('=', $value, 2);
                $ccAvenueCollectedDataArray[$key] = $val;
                if ($key == 'order_status') {
                    $order_status = $val;
                }
            }

            Log::info('CC Avenue Response', $ccAvenueCollectedDataArray);
            $order = Order::where('id', $ccAvenueCollectedDataArray['order_id'])->firstOrFail();
            $this->storeCcAvenueData($order, $ccAvenueCollectedDataArray);
            switch ($order_status) {
                case 'Success':
                    Session::put('payment_data', $order);
                    $this->isSuccessful = true;

                    return Inertia::render('Checkout/Woohoo/AutoSubmit', [
                        'submitUrl' => route('woohoo.createOrder'),
                    ]);
                case 'Failure':
                    $transactionStatusMessage = 'Payment Failed. Please try again.';
                    break;
                case 'Aborted':
                    $transactionStatusMessage = 'Payment Aborted. Please try again later.';
                    break;
                case 'Invalid':
                    $transactionStatusMessage = 'Invalid Payment. Please check your details and try again.';
                    break;
                case 'Timeout':
                    $transactionStatusMessage = 'Payment Timeout. Please try again.';
                    break;
                default:
                    $transactionStatusMessage = 'Unknown Payment Status. Please contact support.';
                    break;
            }

            return Inertia::render('Checkout/OrderStatus', [
                'transactionStatusMessage' => $transactionStatusMessage,
                'isSuccessful' => $this->isSuccessful,
            ]);
        } catch (Exception $e) {
            Log::error('Error in responseCcavenue: '.$e->getMessage());

            return Inertia::render('Checkout/OrderStatus', [
                'transactionStatusMessage' => 'An error occurred while processing your payment.',
                'isSuccessful' => false,
            ]);
        }
    }

    protected function storeCcAvenueData(Order $order, $ccAvenueCollectedDataArray)
    {

        // Payment persistence deferred: use consolidated {@see \App\Models\Payment} (gateway ccavenue) when this path is re-enabled.
        Log::info('Payment processing for order_id: '.$order->id);

        return;

        Log::info('CC Avenue data stored successfully');
    }

    protected function updateOrderFromCcAvenue($sessionId, Request $request)
    {
        try {
            $Order = Order::where('id', $sessionId)->firstOrFail();
            Log::info('Order is this', $Order->toArray());

            // SECURITY: Never update amount, discount, denomination, or quantity from request
            // These values are calculated on the backend and stored in the database
            // Only allow updating billing/sender information
            $updateData = [
                'sender_first_name' => $request->billing_name ?? $Order->sender_first_name,
                'sender_email' => $request->billing_email ?? $Order->sender_email,
                'sender_phone_no' => $request->billing_tel ?? $Order->sender_phone_no,
                'sender_post_code' => $request->billing_zip ?? $Order->sender_post_code,
                'sender_address_1' => $request->billing_address ?? $Order->sender_address_1,
                'sender_address_2' => $request->billing_address_two ?? $Order->sender_address_2,
                'sender_city' => $request->billing_city ?? $Order->sender_city,
                'sender_state' => $request->billing_state ?? $Order->sender_state,
                'gst_number' => $request->billing_gst_number ?? $Order->gst_number ?: 'Unregistered',
                'country' => $request->billing_country ?? $Order->country,
                // SECURITY: Do not update these fields from request - use database values only
                // 'sku' => $Order->sku, // Keep original SKU
                // 'amount_payable_after_discount' => $Order->amount_payable_after_discount, // Keep original amount
                // 'discounted_amount_value' => $Order->discounted_amount_value, // Keep original discount
            ];

            // Log any attempt to modify financial data
            if ($request->has('amount') || $request->has('quantity') || $request->has('denomination')) {
                Log::warning('⚠️ Attempt to modify financial data via CCAvenue updateOrderFromCcAvenue', [
                    'order_id' => $Order->id,
                    'user_id' => $Order->user_id,
                    'ip_address' => $request->ip(),
                    'requested_amount' => $request->input('amount'),
                    'requested_quantity' => $request->input('quantity'),
                    'requested_denomination' => $request->input('denomination'),
                    'database_amount' => $Order->amount_payable_after_discount,
                ]);
            }

            $Order->update($updateData);

            // Fetch the related OrderSummary using the same order ID
            $orderSummary = OrderSummary::where('order_id', $Order->id)->first();

            if ($orderSummary) {
                // Prepare update data for OrderSummary
                $orderSummary->update([
                    'sender_name' => $updateData['sender_first_name'],  // Update sender name
                    'sender_email' => $updateData['sender_email'],      // Update sender email
                    'sender_phone' => $updateData['sender_phone_no'],   // Update sender phone number
                ]);
                Log::info("OrderSummary updated for order_id: {$Order->id}");
            } else {
                Log::warning("OrderSummary not found for order_id: {$Order->id}");
            }

            return true;
        } catch (Exception $e) {
            Log::error("Record not found for session ID: $sessionId - {$e->getMessage()}");

            return false;
        }
    }
}

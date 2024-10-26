<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use App\Models\QsOrder;
use App\Models\CcAvenuePayment;
use App\Models\OrderSummary;
use App\Models\User;
use App\Helpers\CommonHelper;
use Carbon\Carbon;
use Auth;
use DB;
use Mail;
use App\Models\QsProduct;
use PDF;
use App\Models\GiftCard;
use Config;
use Exception;
use Illuminate\Http\Client\ConnectionException;
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

            // Log request data for debugging
            Log::info('Process payment request', $request->all());

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
            $sessionId = session('session_qs_order_id');
            if (!$this->updateQsOrder($sessionId, $request)) {
                Log::error('Record not found for session ID: ' . $sessionId);
                return view('order.order-status', ['errorMessage' => 'Record not found for session ID']);
            }

            $paymentData = $this->preparePaymentData($sessionId, $request);
            $encryptedData = $this->encryptPaymentData($paymentData);
            $accessCode = config('paymentconfig.access_code');
            $ccavenueApiEndpoint = config('paymentconfig.ccavenue_api_endpoint');

            return view('paymentFolder.ccavRequestHandler', compact('encryptedData', 'accessCode', 'ccavenueApiEndpoint'));

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation exceptions
            Log::error('Validation failed', [
                'errors' => $e->errors(),
                'message' => $e->getMessage()
            ]);
            return redirect()->back()->withErrors($e->errors())->withInput();

        } catch (\Exception $e) {
            // Handle general exceptions
            Log::error('Payment processing failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return view('order.order-status', ['errorMessage' => 'An error occurred while processing your payment.']);
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
        $paymentData = $request->all();

        // Grouping session variables
        $paymentData['order_id'] = $sessionId;

        $orderData = QsOrder::where('id', $sessionId)->first();
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
            $qsOrder = QsOrder::where('id', $request->orderNo)->with('product')->firstOrFail();
            $qsProductSlug = $qsOrder->product->slug;
            $qsOrder->update(['order_status' => 'Cancelled']);
            Log::info("Order ID {$qsOrder->id} status updated to Cancelled");
            // return redirect()->route('checkoutPage', ['slug' => $qsProductSlug])->with('error-message', 'Payment was cancelled. Please try again.');
            return view('order.order-status', ['transactionStatusMessage' => 'Payment was cancelled. Please try again.', 'isSuccessful' => false]);
        } catch (\Exception $e) {
            Log::error("Error handling payment cancellation: {$e->getMessage()}");
            // return redirect()->route('checkoutPage')->with('error-message', 'Order not found.');
            return view('order.order-status', ['transactionStatusMessage' => 'An error occurred while processing your payment.', 'isSuccessful' => false]);
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
            $qsOrderDetails = QsOrder::where('id', $ccAvenueCollectedDataArray['order_id'])->firstOrFail();
            $this->storeCcAvenueData($qsOrderDetails, $ccAvenueCollectedDataArray);
            switch ($order_status) {
                case 'Success':
                    Session::put('payment_data', $qsOrderDetails);
                    $this->isSuccessful = true;
                    return view('woohoo.redirect-to-woohoo');
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
            return view('order.order-status', ['transactionStatusMessage' => $transactionStatusMessage, 'isSuccessful' => $this->isSuccessful,]);
        } catch (\Exception $e) {
            Log::error('Error in responseCcavenue: ' . $e->getMessage());
            return view('order.order-status', ['transactionStatusMessage' => 'An error occurred while processing your payment.', 'isSuccessful' => false]);
        }
    }
    protected function storeCcAvenueData($qsOrderDetails, $ccAvenueCollectedDataArray)
    {


        // Find the existing payment record by order_id
        $existingPayment = CcAvenuePayment::where('order_id', $qsOrderDetails->id)->first();



        if (!$existingPayment) {
            Log::error('Payment record not found for order_id: ' . $qsOrderDetails->order_id);
            return;
        }


        $commonFields = ['order_id', 'tracking_id', 'bank_ref_no', 'order_status', 'failure_message', 'payment_mode', 'card_name', 'status_code', 'status_message', 'currency', 'amount', 'billing_name', 'billing_address', 'billing_city', 'billing_state', 'billing_zip', 'billing_country', 'billing_tel', 'billing_email', 'delivery_name', 'delivery_address', 'delivery_city', 'delivery_state', 'delivery_zip', 'delivery_country', 'delivery_tel', 'merchant_param1', 'merchant_param2', 'merchant_param3', 'merchant_param4', 'merchant_param5', 'vault', 'offer_type', 'offer_code', 'discount_value', 'mer_amount', 'eci_value', 'retry', 'response_code', 'billing_notes', 'trans_date', 'bin_country'];
        foreach ($commonFields as $field) {
            if (isset($ccAvenueCollectedDataArray[$field])) {
                $existingPayment->{$field} = $ccAvenueCollectedDataArray[$field];
            }
        }
        $existingPayment->save();


        $existingOrderSummary = OrderSummary::where('order_id', $existingPayment->order_id)->first();
        $existingOrderSummary->payment_status = $existingPayment->order_status;
        $existingOrderSummary->save();



        Log::info('CC Avenue data stored successfully');
    }
    protected function updateQsOrder($sessionId, Request $request)
    {
        try {
            $qsOrder = QsOrder::where('id', $sessionId)->firstOrFail();
            Log::info('qsorder is this', $qsOrder->toArray());
            $updateData = ['sender_first_name' => $request->billing_name ?? $qsOrder->sender_first_name, 'sender_email' => $request->billing_email ?? $qsOrder->sender_email, 'sender_phone_no' => $request->billing_tel ?? $qsOrder->sender_phone_no, 'sender_post_code' => $request->billing_zip ?? $qsOrder->sender_post_code, 'sender_address_1' => $request->billing_address ?? $qsOrder->sender_address_1, 'sender_address_2' => $request->billing_address_two ?? $qsOrder->sender_address_2, 'sender_city' => $request->billing_city ?? $qsOrder->sender_city, 'sender_state' => $request->billing_state ?? $qsOrder->sender_state, 'sku' => $request->has('sku') ? $request->sku : $qsOrder->sku, 'amount_payable_after_discount' => $request->has('amount') ? $request->amount : $qsOrder->amount_payable_after_discount, 'discounted_amount_value' => $request->has('quantity') && $request->has('denomination') && $request->has('amount') ? round($request->quantity * $request->denomination - $request->amount, 3) : $qsOrder->discounted_amount_value, 'gst_number' => $request->billing_gst_number ?? $qsOrder->gst_number ?: 'Unregistered', 'country' => $request->billing_country ?? $qsOrder->country,];
            $qsOrder->update($updateData);


            // Fetch the related OrderSummary using the same order ID
            $orderSummary = OrderSummary::where('order_id', $qsOrder->id)->first();



            if ($orderSummary) {
                // Prepare update data for OrderSummary
                $orderSummary->update([
                    'sender_name' => $updateData['sender_first_name'],  // Update sender name
                    'sender_email' => $updateData['sender_email'],      // Update sender email
                    'sender_phone' => $updateData['sender_phone_no'],   // Update sender phone number
                ]);
                Log::info("OrderSummary updated for order_id: {$qsOrder->id}");
            } else {
                Log::warning("OrderSummary not found for order_id: {$qsOrder->id}");
            }




            return true;
        } catch (\Exception $e) {
            Log::error("Record not found for session ID: $sessionId - {$e->getMessage()}");
            return false;
        }
    }
}

<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use App\Models\QsOrder;
use App\Models\CcAvenuePayment;
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
        Log::info('Session CSRF Token: ' . session()->token());
        Log::info('Request CSRF Token: ' . $request->input('_token'));
        Log::info('Process payment request', $request->all());
        $sessionId = session('session_qs_order_id');
        if (!$this->updateQsOrder($sessionId, $request)) {
            return view('order.order-status', ['errorMessage' => 'Record not found for session ID']);
        }
        $paymentData = $this->preparePaymentData($sessionId, $request);
        $encryptedData = $this->encryptPaymentData($paymentData);
        $accessCode = config('paymentconfig.access_code');
        $ccavenueApiEndpoint = config('paymentconfig.ccavenue_api_endpoint');
        return view('paymentFolder.ccavRequestHandler', compact('encryptedData', 'accessCode', 'ccavenueApiEndpoint'));
    }
    protected function preparePaymentData($sessionId, Request $request)
{
    // Retrieve all request data
    $paymentData = $request->all();

    // Add or override values with session and configuration data
    $paymentData['order_id'] = $sessionId;
    $paymentData['denomination'] = session('denomination');
    $paymentData['amount'] = session('total_payable_amount_after_discount');
    $paymentData['quantity'] = session('quantity');

    // Retrieve static values securely from configuration
    $paymentData['numericCode'] = config('paymentconfig.numeric_code', '356');
    $paymentData['currency'] = config('paymentconfig.currency', 'INR');
    $paymentData['language'] = config('paymentconfig.language', 'EN');

    // URLs for redirect and cancellation
    $paymentData['redirect_url'] = route('response_ccavenue');
    $paymentData['cancel_url'] = url('payment-cancel');

    // Retrieve sensitive values from configuration
    $paymentData['merchant_id'] = config('paymentconfig.merchant_id');

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
            return redirect()->route('checkoutPage', ['slug' => $qsProductSlug])->with('error-message', 'Payment was cancelled. Please try again.');
        } catch (\Exception $e) {
            Log::error("Error handling payment cancellation: {$e->getMessage()}");
            return redirect()->route('checkoutPage')->with('error-message', 'Order not found.');
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
        $newCcAvenueOrder = new CcAvenuePayment();
        $newCcAvenueOrder->fill(['user_id' => $qsOrderDetails->user_id, 'price' => $qsOrderDetails->denomination, 'qty' => $qsOrderDetails->quantity,]);
        $commonFields = ['order_id', 'tracking_id', 'bank_ref_no', 'order_status', 'failure_message', 'payment_mode', 'card_name', 'status_code', 'status_message', 'currency', 'amount', 'billing_name', 'billing_address', 'billing_city', 'billing_state', 'billing_zip', 'billing_country', 'billing_tel', 'billing_email', 'delivery_name', 'delivery_address', 'delivery_city', 'delivery_state', 'delivery_zip', 'delivery_country', 'delivery_tel', 'merchant_param1', 'merchant_param2', 'merchant_param3', 'merchant_param4', 'merchant_param5', 'vault', 'offer_type', 'offer_code', 'discount_value', 'mer_amount', 'eci_value', 'retry', 'response_code', 'billing_notes', 'trans_date', 'bin_country'];
        foreach ($commonFields as $field) {
            if (isset($ccAvenueCollectedDataArray[$field])) {
                $newCcAvenueOrder->{$field} = $ccAvenueCollectedDataArray[$field];
            }
        }
        $newCcAvenueOrder->save();
        Log::info('CC Avenue data stored successfully');
    }
    protected function updateQsOrder($sessionId, Request $request)
    {


        try {
            $qsOrder = QsOrder::where('id', $sessionId)->firstOrFail();
            Log::info('qsorder is this', $qsOrder->toArray());


            $updateData = [
                'sender_first_name' => $request->billing_name ?? $qsOrder->sender_first_name,
                'sender_email' => $request->billing_email ?? $qsOrder->sender_email,
                'sender_phone_no' => $request->billing_tel ?? $qsOrder->sender_phone_no,
                'sender_post_code' => $request->billing_zip ?? $qsOrder->sender_post_code,
                'sender_address_1' => $request->billing_address ?? $qsOrder->sender_address_1,
                'sender_address_2' => $request->billing_address_two ?? $qsOrder->sender_address_2,
                'sender_city' => $request->billing_city ?? $qsOrder->sender_city,
                'sender_state' => $request->billing_state ?? $qsOrder->sender_state,
                'sku' => $request->has('sku') ? $request->sku : $qsOrder->sku,  // Only update if present
                'amount_payable_after_discount' => $request->has('amount') ? $request->amount : $qsOrder->amount_payable_after_discount,  // Only update if present
                'discounted_amount_value' => $request->has('quantity') && $request->has('denomination') && $request->has('amount')
                    ? round($request->quantity * $request->denomination - $request->amount, 3)
                    : $qsOrder->discounted_amount_value,  // Calculate only if quantity, denomination, and amount are present
                'gst_number' => $request->billing_gst_number ?? $qsOrder->gst_number ?: 'Unregistered',
                'country' => $request->billing_country ?? $qsOrder->country,
            ];
            $qsOrder->update($updateData);
            return true;
        } catch (\Exception $e) {
            Log::error("Record not found for session ID: $sessionId - {$e->getMessage()}");
            return false;
        }
    }
}

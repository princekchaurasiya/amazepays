<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Session;
use Illuminate\Support\Facades\Redirect;
use App\Models\QsOrder;
use App\Models\CcAvenuePayment;
use App\Helpers\CommonHelper;
use Carbon;
use GuzzleHttp\Client;
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

    public function __construct(CryptoController $cryptoController)
    {
        $this->cryptoController = $cryptoController;
    }

    public function processPayment(Request $request)
    {
        Log::info('Process payment request', $request->all());

        $sessionId = Session::get('session_qs_order_id');

        if (!$this->updateQsOrder($sessionId, $request)) {
            return view('order.order-status', ['errorMessage' => 'Record not found for session ID']);
        }

        $paymentData = $this->preparePaymentData($sessionId, $request);

        // Encrypt payment data
        $merchantData = '';
        foreach ($paymentData as $key => $value) {
            $merchantData .= $key . '=' . urlencode($value) . '&';
        }
        $workingKey = config('paymentconfig.working_key');
        $encryptedData = $this->cryptoController->encryptCCAvenue($merchantData, $workingKey);

        // Get access code and endpoint
        $accessCode = config('paymentconfig.access_code');
        $ccavenueApiEndpoint = config('paymentconfig.ccavenue_api_endpoint');

        return view('paymentFolder.ccavRequestHandler', compact('encryptedData', 'accessCode', 'ccavenueApiEndpoint'));
    }

    protected function preparePaymentData($sessionId, Request $request)
    {
        $paymentData = $request->all();
        $paymentData['order_id'] = $sessionId;
        $paymentData['merchant_id'] = config('paymentconfig.merchant_id');

        return $paymentData;
    }

    public function handlePaymentCancellation(Request $request)
    {
        // Retrieve the order and the associated product using eager loading
        $qsOrder = QsOrder::where('id', $request->orderNo)
            ->with('product')
            ->first();

        // Check if the order exists
        if ($qsOrder) {
            $qsProductSlug = $qsOrder->product->slug;

            // Update the order status to cancelled
            $qsOrder->update(['order_status' => 'Cancelled']);

            Log::info("Order ID {$qsOrder->id} status updated to Cancelled");
        } else {
            Log::error("Order not found for ID: {$request->orderNo}");

            // If order is not found, we cannot determine the product slug
            return redirect()->route('checkoutPage')->with('error-message', 'Order not found.');
        }

        // Redirect with the product slug

        return redirect()
            ->route('checkoutPage', ['slug' => $qsProductSlug])
            ->with('error-message', 'Payment was cancelled. Please try again.');
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

        Log::info('************* CC Avenue Response ***************');
        Log::info($decryptValues);

        $qsOrderDetails = QsOrder::where('id', $ccAvenueCollectedDataArray['order_id'])->first();

        if ($qsOrderDetails) {
            $this->storeCcAvenueData($qsOrderDetails, $ccAvenueCollectedDataArray);

            log::info("Data processed for payment status: $order_status");

            switch ($order_status) {
                case 'Success':
                    Session::put('payment_data', $qsOrderDetails);
                    $transactionStatusMessage = 'Payment Successful!';
                    $isSuccessful = true;
                    return view('woohoo.redirect-to-woohoo');
                    break;
                case 'Failure':
                    $transactionStatusMessage = 'Payment Failed. Please try again.';
                    $isSuccessful = false;
                    break;
                case 'Aborted':
                    $transactionStatusMessage = 'Payment Aborted. Please try again later.';
                    $isSuccessful = false;
                    break;
                case 'Invalid':
                    $transactionStatusMessage = 'Invalid Payment. Please check your details and try again.';
                    $isSuccessful = false;
                    break;
                case 'Timeout':
                    $transactionStatusMessage = 'Payment Timeout. Please try again.';
                    $isSuccessful = false;
                    break;
                default:
                    $transactionStatusMessage = 'Unknown Payment Status. Please contact support.';
                    $isSuccessful = false;
                    break;
            }

            return view('order.order-status', [
                'transactionStatusMessage' => $transactionStatusMessage,
                'isSuccessful' => $isSuccessful,
            ]);
        } else {
            Log::error('Order not found for ID: ' . $ccAvenueCollectedDataArray['order_id']);
            return view('order.order-status', ['transactionStatusMessage' => 'Order not found!']);
        }
    } catch (\Exception $e) {
        Log::error('Error in responseCcavenue: ' . $e->getMessage());
        return view('order.order-status', ['transactionStatusMessage' => 'An error occurred while processing your payment.']);
    }
}


    protected function storeCcAvenueData($qsOrderDetails, $ccAvenueCollectedDataArray)
    {
        $newCcAvenueOrder = new CcAvenuePayment();
        $newCcAvenueOrder->user_id = $qsOrderDetails->user_id;
        $newCcAvenueOrder->price = $qsOrderDetails->denomination;
        $newCcAvenueOrder->qty = $qsOrderDetails->quantity;

        $commonFields = ['order_id', 'tracking_id', 'bank_ref_no', 'order_status', 'failure_message', 'payment_mode', 'card_name', 'status_code', 'status_message', 'currency', 'amount', 'billing_name', 'billing_address', 'billing_city', 'billing_state', 'billing_zip', 'billing_country', 'billing_tel', 'billing_email', 'delivery_name', 'delivery_address', 'delivery_city', 'delivery_state', 'delivery_zip', 'delivery_country', 'delivery_tel', 'merchant_param1', 'merchant_param2', 'merchant_param3', 'merchant_param4', 'merchant_param5', 'vault', 'offer_type', 'offer_code', 'discount_value', 'mer_amount', 'eci_value', 'retry', 'response_code', 'billing_notes', 'trans_date', 'bin_country'];

        foreach ($commonFields as $field) {
            if (isset($ccAvenueCollectedDataArray[$field])) {
                $newCcAvenueOrder->{$field} = $ccAvenueCollectedDataArray[$field];
            }
        }

        $newCcAvenueOrder->save();

        log::info('cc avenue data stored successfully');
    }

    protected function updateQsOrder($sessionId, Request $request)
    {
        $qsOrder = QsOrder::where('id', $sessionId)->first();

        // dd($request->all(),$qsOrder);

        if (!$qsOrder) {
            Log::error("Record not found for session ID: $sessionId");
            return false;
        }

        Log::info('Order before update: ', $qsOrder->toArray());


        $updateData = [
            'sender_first_name' => $request->billing_name,
            'sender_email' => $request->billing_email,
            'sender_phone_no' => $request->billing_tel,
            'sender_post_code' => $request->billing_zip,
            'sender_address_1' => $request->billing_address,
            'sender_address_2' => $request->billing_address_two,
            'sender_city' => $request->billing_city,
            'sender_state' => $request->billing_state,
            'sku' => $request->sku,
            'amount_payable_after_discount' => $request->amount,
            'discounted_amount_value' => round($request->quantity * $request->denomination - $request->amount, 3),
            'gst_number' => $request->billing_gst_number,
            'country' => $request->billing_country,
        ];

        Log::info('Update data: ', $updateData);

        $qsOrder->update($updateData);

        Log::info('Order after update: ', $qsOrder->toArray());

        return true;
    }
}

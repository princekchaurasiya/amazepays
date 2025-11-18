<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\QsOrder;
use App\Http\Controllers\WoohooOrderController;
use App\Models\UnlimitPayment;

class WoohooProcessingController extends Controller
{

   public function handleReturn(Request $request)
{
    Log::info("🔄 Unlimit Return Hit", [
        'query'    => $request->query(),
        'full_url' => $request->fullUrl(),
    ]);

    $merchantOrderId = $request->query('merchant_order_id');
    $status          = $request->query('status');

    if (!$merchantOrderId) {
        Log::error("❌ Missing merchant_order_id in return URL");
        return "Invalid return data.";
    }

    // Fetch payment from DB
    $payment = UnlimitPayment::where('merchant_order_id', $merchantOrderId)->first();

    if (!$payment) {
        Log::error("❌ No payment found for merchant_order_id (return)", [
            'merchant_order_id' => $merchantOrderId
        ]);
        return "Unable to verify payment. Please contact support.";
    }

    // Update payment status
    $payment->payment_status = $status;
    $payment->save();

    Log::info("✅ Payment updated via RETURN page", [
        'merchant_order_id' => $merchantOrderId,
        'status' => $status
    ]);

    // Redirect user to Woohoo/Success UI
    return view("woohoo.processing-woohoo", [
        "amount" => $payment->amount,
        "merchant_order_id" => $merchantOrderId,
        "status" => $status
    ]);
}
    /**
     * Process the Woohoo order creation after successful payment
     */
public function createOrder(Request $request)
{
    try {

        // Get order data from session
       // $paymentReturnData = session('payment_return_data');

       /* if (!$paymentReturnData) {
            Log::warning('No payment return data found in session');
            return redirect()->route('my-order')
                ->with('error', 'No payment data found. Please contact support.');
        }

        $orderId = $paymentReturnData['order_id'];
        $status = $paymentReturnData['status'];

        Log::info('Processing Woohoo order creation', [
            'order_id' => $orderId,
            'status' => $status
        ]);*/

        // Find the QsOrder
        $merchantOrderId = UnlimitPayment::where('order_id', $orderId)->value('merchant_order_id');
        $qsOrder = QsOrder::find($merchantOrderId);

        if (!$qsOrder) {
            Log::error('QsOrder not found for processing', ['order_id' => $orderId]);
            return redirect()->route('my-order')
                ->with('error', 'Order not found. Please contact support.');
        }

        // If status empty, fetch from DB
        if (empty($status)) {

            $unlimitPayment = \App\Models\UnlimitPayment::where('order_id', $qsOrder->merchant_order_id)->first();

            if ($unlimitPayment) {
                $status = $unlimitPayment->status ??
                          $unlimitPayment->order_status ??
                          'pending';

                Log::info('Using database status for payment check', [
                    'order_id' => $orderId,
                    'db_status' => $status
                ]);
            }
        }

        // Reject if payment failed
        if (!in_array($status, ['success', 'approved', 'completed'])) {
            Log::warning('Payment not successful, cannot create Woohoo order', [
                'order_id' => $orderId,
                'status' => $status
            ]);
            return redirect()->route('my-order')
                ->with('error', 'Payment was not successful. Please try again.');
        }

        // Prevent duplicate Woohoo order
        if ($qsOrder->woohoo_order_id) {
            Log::info('Woohoo order already exists', [
                'order_id' => $orderId,
                'woohoo_order_id' => $qsOrder->woohoo_order_id
            ]);
            return redirect()->route('my-order')
                ->with('success', 'Your order is already being processed.');
        }

        // Create Woohoo order
        $woohooController = new WoohooOrderController();
        $woohooResult = $woohooController->createWoohooOrderRequest($qsOrder);

        if ($woohooResult['success']) {

            // Process order
            $woohooController->saveWoohooOrderResponse($qsOrder, $woohooResult);
            $vouchers = $woohooController->fetchWoohooVouchers($qsOrder);
            $woohooController->storeWoohooVouchers($qsOrder, $vouchers);
            $woohooController->sendWoohooEmails($qsOrder, $vouchers);

            // Clear session
            session()->forget('payment_return_data');
            session()->forget('unlimit_ctx');

            return view('woohoo.response', [
                'order' => $qsOrder,
                'woohoo' => $woohooResult,
                'vouchers' => $vouchers
            ]);

        } else {

            Log::error('Failed to create Woohoo order', [
                'order_id' => $orderId,
                'result' => $woohooResult
            ]);

            return view('woohoo.response', [
                'order' => $qsOrder,
                'woohoo' => $woohooResult,
                'vouchers' => []
            ]);
        }

    } catch (\Exception $e) {

        Log::error('Woohoo processing failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return redirect()->route('my-order')
            ->with('error', 'An unexpected error occurred. Please contact support with your order details.');
    }
}


    /**
     * Show processing status page
     */
    public function showProcessing(Request $request)
    {
       $paymentReturnData = session('payment_return_data');
        
        if (!$paymentReturnData) {
            return redirect()->route('my-order')->with('error', 'No payment data found.');
       }

        return view('woohoo.processing-woohoo', [
            'order_id' => $paymentReturnData['order_id'] ?? 'N/A',
            'status' => $paymentReturnData['status'] ?? 'Processing',
            'amount' => $paymentReturnData['amount'] ?? 0
        ]);
    }


}

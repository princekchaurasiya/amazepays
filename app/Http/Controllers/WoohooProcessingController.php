<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\QsOrder;
use App\Http\Controllers\WoohooOrderController;

class WoohooProcessingController extends Controller
{
    /**
     * Process the Woohoo order creation after successful payment
     */
    public function createOrder(Request $request)
    {
        try {
            // Get order data from session (set by UnlimitPaymentController)
            $paymentReturnData = session('payment_return_data');
            
            if (!$paymentReturnData) {
                Log::warning('No payment return data found in session');
                return redirect()->route('my-order')->with('error', 'No payment data found. Please contact support.');
            }

            $orderId = $paymentReturnData['order_id'];
            $paymentId = $paymentReturnData['payment_id'];
            $status = $paymentReturnData['status'];

            Log::info('Processing Woohoo order creation', [
                'order_id' => $orderId,
                'payment_id' => $paymentId,
                'status' => $status
            ]);

            // Find the QsOrder
            $qsOrder = QsOrder::find($orderId);
            
            if (!$qsOrder) {
                Log::error('QsOrder not found for processing', ['order_id' => $orderId]);
                return redirect()->route('my-order')->with('error', 'Order not found. Please contact support.');
            }

            // Check if payment was successful
            // If status is null/empty, check the database for the actual payment status
            if (empty($status)) 
                {
            $unlimitPayment = \App\Models\UnlimitPayment::where('order_id', $qsOrder->merchant_order_id)->first();
                    if ($unlimitPayment) 
                        {
                        $status = $unlimitPayment->status ?? $unlimitPayment->order_status ?? 'pending';
                        Log::info('Using database status for payment check', [
                            'order_id' => $orderId,
                            'db_status' => $status
                        ]);
                        }
                }

            
            if (!in_array($status, ['success', 'approved', 'completed'])) {
                Log::warning('Payment not successful, cannot create Woohoo order', [
                    'order_id' => $orderId,
                    'status' => $status
                ]);
                return redirect()->route('my-order')->with('error', 'Payment was not successful. Please try again.');
            }

            // Check if Woohoo order already exists
            if ($qsOrder->woohoo_order_id) {
                Log::info('Woohoo order already exists', [
                    'order_id' => $orderId,
                    'woohoo_order_id' => $qsOrder->woohoo_order_id
                ]);
                return redirect()->route('my-order')->with('success', 'Your order is already being processed.');
            }

            // Create Woohoo order
            $woohooController = new WoohooOrderController();
            $woohooResult = $woohooController->createWoohooOrderRequest($qsOrder);

            if ($woohooResult && isset($woohooResult['success']) && $woohooResult['success']) {
                Log::info('Woohoo order created successfully', [
                    'order_id' => $orderId,
                    'woohoo_order_id' => $qsOrder->woohoo_order_id ?? 'N/A'
                ]);

                // Clear the session data
                session()->forget('payment_return_data');
                session()->forget('unlimit_ctx');

                return redirect()->route('my-order')->with('success', 'Your order has been processed successfully! You will receive an email confirmation shortly.');
            } else {
                Log::error('Failed to create Woohoo order', [
                    'order_id' => $orderId,
                    'result' => $woohooResult
                ]);

                return redirect()->route('my-order')->with('error', 'There was an issue processing your order. Our team has been notified and will contact you shortly.');
            }

        } catch (\Exception $e) {
            Log::error('Woohoo processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('my-order')->with('error', 'An unexpected error occurred. Please contact support with your order details.');
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
            'payment_id' => $paymentReturnData['payment_id'] ?? 'N/A',
            'order_id' => $paymentReturnData['order_id'] ?? 'N/A',
            'status' => $paymentReturnData['status'] ?? 'Processing',
            'amount' => $paymentReturnData['amount'] ?? 0
        ]);
    }
}

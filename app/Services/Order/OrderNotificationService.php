<?php

namespace App\Services\Order;

use App\Mail\OrderConfirmationMail;
use App\Mail\OrderFailedMail;
use App\Mail\VoucherDeliveryMail;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Handles all order-related notifications (email, SMS).
 * Called by jobs after order creation / fulfillment -- not inline in controllers.
 */
class OrderNotificationService
{
    /**
     * Send order confirmation to the purchaser.
     */
    public function sendOrderConfirmation(Order $order): void
    {
        try {
            $user = $order->user;
            $email = $order->receiver_email ?? $user->email;
            Mail::to($email)->send(new OrderConfirmationMail($order));

            Log::info('Order confirmation sent', ['order_id' => $order->id, 'email' => $email]);
        } catch (\Throwable $e) {
            Log::error('Failed to send order confirmation', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send voucher codes to the recipient.
     */
    public function sendVoucherDelivery(Order $order, array $voucherCodes): void
    {
        try {
            $recipientEmail = $order->gift_option === 'send_as_gift'
                ? $order->receiver_email
                : $order->user->email;
            Mail::to($recipientEmail)->send(new VoucherDeliveryMail($order, $voucherCodes));

            Log::info('Voucher delivery sent', ['order_id' => $order->id]);
        } catch (\Throwable $e) {
            Log::error('Failed to send voucher delivery email', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send order failure notification to the purchaser.
     */
    public function sendOrderFailure(Order $order, string $reason): void
    {
        try {
            Mail::to($order->user->email)->send(new OrderFailedMail($order, $reason));
        } catch (\Throwable $e) {
            Log::error('Failed to send order failure notification', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

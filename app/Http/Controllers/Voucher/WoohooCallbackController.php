<?php

namespace App\Http\Controllers\Voucher;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Support\Http\ResponsePayload;
use Illuminate\Http\Request;

/**
 * Handles Woohoo voucher fulfillment webhook callbacks.
 */
class WoohooCallbackController extends Controller
{
    use ApiResponse;

    public function handle(Request $request): ResponsePayload
    {
        // TODO: Validate Woohoo webhook signature, extract order reference,
        //       update order status using OrderStatusMachine::transition(),
        //       store voucher codes on the order record.

        $request->attributes->set('is_webhook_ack', true);
        $request->attributes->set('webhook_gateway', 'woohoo');

        return $this->ok('payments.webhook_received');
    }
}

<?php

namespace App\Http\Controllers\Voucher;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Handles Woohoo voucher fulfillment webhook callbacks.
 */
class WoohooCallbackController extends Controller
{
    use ApiResponse;

    public function handle(Request $request): JsonResponse
    {
        // TODO: Validate Woohoo webhook signature, extract order reference,
        //       update order status using OrderStatusMachine::transition(),
        //       store voucher codes on the order record.

        return $this->ok('Webhook received.');
    }
}

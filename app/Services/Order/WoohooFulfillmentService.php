<?php

namespace App\Services\Order;

use App\Http\Controllers\WoohooOrderController;
use App\Models\Order;
use App\Models\UnlimitPayment;

/**
 * Delegates Woohoo voucher fulfillment to the legacy controller implementation
 * until the HTTP layer is fully extracted into plain services.
 */
class WoohooFulfillmentService
{
    private function controller(): WoohooOrderController
    {
        return app(WoohooOrderController::class);
    }

    public function createWoohooOrderRequest(Order $order, UnlimitPayment $payment): mixed
    {
        return $this->controller()->createWoohooOrderRequest($order, $payment);
    }

    public function handleSuccessFullOrder(array $orderCreatedResponse): mixed
    {
        return $this->controller()->handleSuccessFullOrder($orderCreatedResponse);
    }

    public function updateOrderFromWoohooResponse(mixed $orderCreatedResponse): mixed
    {
        return $this->controller()->syncOrderFromWoohooResponse($orderCreatedResponse);
    }
}

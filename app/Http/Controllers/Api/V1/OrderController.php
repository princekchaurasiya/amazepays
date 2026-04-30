<?php

namespace App\Http\Controllers\Api\V1;

use App\Data\BillingData;
use App\Data\OrderData;
use App\Enums\ResponseCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PlaceOrderRequest;
use App\Http\Requests\Order\RefundOrderRequest;
use App\Http\Traits\ApiResponse;
use App\Models\GiftCard;
use App\Models\Order;
use App\Models\SecurityEventLog;
use App\Services\Order\OrderCreationService;
use App\Services\Order\WoohooGiftCardPersister;
use App\Services\Payment\PaymentService;
use App\Services\SecurityEventService;
use App\Support\Http\ResponsePayload;
use Illuminate\Http\Request;

/**
 * API v1 -- Order endpoints.
 *
 * All monetary calculations happen inside OrderCreationService / PricingService.
 * This controller only validates input, delegates, and formats responses.
 */
class OrderController extends Controller
{
    use ApiResponse;

    public function __construct(
        private OrderCreationService $orderService,
        private SecurityEventService $securityEvents,
        private PaymentService $paymentService,
        private WoohooGiftCardPersister $giftCardPersister,
    ) {}

    /** List authenticated user's orders with pagination. */
    public function index(Request $request): ResponsePayload
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return $this->paginated($orders);
    }

    /** Show a single order (scoped to the authenticated user). */
    public function show(Request $request, Order $order): ResponsePayload
    {
        if ($order->user_id !== $request->user()->id) {
            return $this->notFound();
        }

        $order->loadMissing(['giftCards']);
        $orderPayload = $order->toArray();
        $orderPayload['gift_cards'] = $this->giftCardPersister->apiSummariesForOrder($order);

        return $this->ok('orders.retrieved', ['order' => $orderPayload]);
    }

    /**
     * Retrieve the voucher code for a completed order.
     * Protected by step-up auth + transaction PIN (via middleware).
     */
    public function getVoucherCode(Request $request, Order $order): ResponsePayload
    {
        if ($order->user_id !== $request->user()->id) {
            return $this->notFound();
        }

        $done = strtolower((string) ($order->status ?? '')) === 'completed'
            || strtoupper((string) ($order->order_status ?? '')) === 'COMPLETE';
        if (! $done) {
            return $this->error(ResponseCode::VALIDATION_FAILED, 'orders.not_completed', 422);
        }

        $protection = config('security.voucher_protection', []);

        if (($protection['block_vpn_access'] ?? false) && $request->attributes->get('vpn_flagged')) {
            $this->securityEvents->log(
                SecurityEventLog::EVENT_VOUCHER_ACCESS_VPN,
                SecurityEventLog::SEVERITY_HIGH,
                null,
                ['order_id' => $order->id, 'user_id' => $request->user()->id]
            );

            return $this->error(ResponseCode::FORBIDDEN, 'orders.voucher_vpn_blocked', 403);
        }

        $viewCacheKey = "voucher_views:{$request->user()->id}";
        $views = \Cache::get($viewCacheKey, 0);
        $maxViews = $protection['max_views_per_hour'] ?? 20;

        if ($views >= $maxViews) {
            return $this->error(ResponseCode::TOO_MANY_REQUESTS, 'orders.voucher_rate_limited', 429);
        }

        \Cache::put($viewCacheKey, $views + 1, 3600);
        $order->increment('code_view_count');
        $order->update(['last_code_viewed_at' => now()]);

        $order->loadMissing(['giftCards']);
        $fromCard = $order->giftCards()
            ->orderBy('id')
            ->get()
            ->first(static function (GiftCard $gc): bool {
                $n = $gc->card_number_encrypted;
                $p = $gc->card_pin_encrypted;

                return ($n !== null && $n !== '') || ($p !== null && $p !== '');
            });

        if ($fromCard !== null) {
            return $this->ok('orders.voucher_retrieved', [
                'voucher_code' => (string) ($fromCard->card_number_encrypted ?? ''),
                'pin' => (string) ($fromCard->card_pin_encrypted ?? ''),
                'expiry_date' => $fromCard->valid_until?->format('Y-m-d'),
                'product_name' => $order->product_name,
                'gift_card_id' => $fromCard->id,
                'viewed_at' => now()->toISOString(),
            ]);
        }

        return $this->ok('orders.voucher_retrieved', [
            'voucher_code' => $order->voucher_code,
            'pin' => $order->voucher_pin,
            'expiry_date' => $order->expiry_date,
            'product_name' => $order->product_name,
            'viewed_at' => now()->toISOString(),
        ]);
    }

    /**
     * Place a new order.
     *
     * Monetary values are calculated server-side by PricingService.
     * The request only carries product_id, quantity, denomination, and payment method.
     */
    public function placeOrder(PlaceOrderRequest $request): ResponsePayload
    {
        $validated = $request->validated();
        $user = $request->user();

        $orderData = OrderData::fromValidated($validated);

        $billingData = new BillingData(
            name: $user->name,
            email: $user->email,
            phone: $user->mobile ?? '',
            address: $user->billing_address ?? '',
            city: $user->billing_city ?? '',
            state: $user->billing_state ?? '',
            zip: $user->billing_zip ?? '',
        );

        $order = $this->orderService->create($user, $orderData, $billingData);

        $payload = [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'grand_total' => $order->grand_total,
            'status' => $order->status,
        ];

        if ($orderData->paymentMethod !== 'wallet') {
            $init = $this->paymentService->initiate($order, $orderData->paymentMethod, $user);
            $payload['payment'] = [
                'success' => $init->success,
                'redirect_url' => $init->redirectUrl,
                'payment_token' => $init->paymentToken,
                'gateway_order_id' => $init->gatewayOrderId,
                'error' => $init->error,
            ];
        }

        return $this->created('orders.placed', $payload);
    }

    public function refund(RefundOrderRequest $request, Order $order): ResponsePayload
    {
        if ($order->user_id !== $request->user()->id) {
            return $this->notFound();
        }

        $validated = $request->validated();
        $gateway = (string) $validated['gateway'];
        $reason = (string) ($validated['reason'] ?? 'refund_requested');

        $amountMinor = $validated['amount_minor'] ?? null;
        if ($amountMinor === null) {
            $amountMinor = $order->grand_total_minor
                ?? (is_numeric($order->grand_total ?? null) ? (int) round(((float) $order->grand_total) * 100) : null)
                ?? 0;
        }

        $amount = ((int) $amountMinor) / 100;

        $result = $this->paymentService->refund(
            $order,
            $amount,
            $reason,
            $gateway,
            isset($validated['transaction_id']) ? (string) $validated['transaction_id'] : null
        );

        return $this->ok('payments.refund_initiated', [
            'success' => $result->success,
            'refund_id' => $result->refundId,
            'status' => $result->status,
            'error' => $result->error,
            'raw' => $result->raw,
        ]);
    }
}

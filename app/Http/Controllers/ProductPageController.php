<?php

namespace App\Http\Controllers;

use App\Helpers\CheckoutHelper;
use App\Helpers\ProductHelper;
use App\Models\Billing;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\GiftCardTheme;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Checkout\CheckoutOrderPayloadFactory;
use App\Services\Checkout\CheckoutReadService;
use App\Services\Checkout\CheckoutWriteService;
use App\Support\BillingRequirementResolver;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ProductPageController extends Controller
{
    /** @var list<string> */
    private const GIFT_CHECKOUT_INPUT_KEYS = [
        'denomination', 'quantity', 'gift_send_option', 'receiver_name',
        'receiver_email', 'receiver_mobile', 'receiver_msg', 'delivery_mode',
        'gift_theme_id', 'gift_message_title', 'sender_first_name', 'gift_delivery_option', 'gift_delivery_at',
    ];

    public function __construct(
        private BillingRequirementResolver $billingRequirements,
        private CheckoutReadService $checkoutReadService,
        private CheckoutWriteService $checkoutWriteService,
        private CheckoutOrderPayloadFactory $checkoutOrderPayloadFactory,
    ) {}

    public function saveGiftCardFormValues(Request $request)
    {
        abort(410, 'Endpoint retired. Use checkout.session.gift_draft.save.');
    }

    /**
     * @deprecated Renamed/split. Use StorefrontProductController + CheckoutSessionController.
     */
    public function storePayNowData(Request $request, $slug)
    {
        abort(410, 'Endpoint retired. Use /checkout/{slug} with the new controllers.');
    }

    public function handleCheckout(Request $request, string $slug)
    {
        abort(410, 'Endpoint retired. Use checkoutPage.post (CheckoutSessionController@submitCheckout).');
    }

    /**
     * @deprecated Retired. Billing updates now live in CheckoutSessionController.
     */
    public function updateSessionData(Request $request)
    {
        abort(410, 'Endpoint retired. Use checkout.session.billing.update.');
    }

    public function addToCart(Request $request, $slug)
    {
        abort(410, 'Endpoint retired. Use storefront.cart.add.');
    }

    public function showCart(Request $request)
    {
        $cart = $request->user()?->cart()->with('items.giftTheme')->first();
        $items = $cart
            ? $cart->items->map(static function (CartItem $item): array {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'slug' => $item->product_slug,
                    'sku' => $item->sku,
                    'product_name' => $item->product_name,
                    'gift_send_option' => $item->gift_send_option,
                    'gift_theme_id' => $item->gift_theme_id,
                    'gift_message_title' => $item->gift_message_title,
                    'gift_delivery_option' => $item->gift_delivery_option,
                    'gift_delivery_at' => $item->gift_delivery_at?->toIso8601String(),
                    'sender_first_name' => $item->sender_first_name,
                    'receiver_name' => $item->receiver_name,
                    'receiver_email' => $item->receiver_email,
                    'receiver_mobile' => $item->receiver_mobile,
                    'receiver_msg' => $item->receiver_msg,
                    'denomination' => (float) $item->denomination,
                    'quantity' => $item->quantity,
                    'line_total' => (float) $item->line_total,
                ];
            })->values()->all()
            : [];

        return Inertia::render('Storefront/Cart', [
            'items' => $items,
        ]);
    }

    public function removeFromCart(Request $request)
    {
        abort(410, 'Endpoint retired. Use storefront.cart.remove.');
    }

    public function clearCart(Request $request)
    {
        abort(410, 'Endpoint retired. Use storefront.cart.clear.');
    }

    public function showCheckoutForm(Request $request, $slug)
    {
        abort(410, 'Endpoint retired. Use StorefrontProductController@showCheckout.');
    }

    /**
     * @param  array<string, mixed>  $prefillFromQuery
     * @return array<string, mixed>
     */
    private function resolveCheckoutData(Order $order, array $prefillFromQuery): array
    {
        if (! $order->id) {
            return $prefillFromQuery;
        }

        return array_filter([
            'denomination' => $order->denomination,
            'quantity' => $order->quantity,
            'gift_send_option' => $order->gift_send_option,
            'receiver_name' => $order->receiver_name,
            'receiver_email' => $order->receiver_email,
            'receiver_mobile' => $order->receiver_mobile,
            'receiver_msg' => $order->receiver_msg,
            'delivery_mode' => $order->delivery_mode,
            'gift_theme_id' => $order->gift_theme_id,
            'gift_message_title' => $order->gift_message_title,
            'sender_first_name' => $order->sender_first_name,
            'gift_delivery_option' => $order->gift_delivery_option,
            'gift_delivery_at' => $order->gift_delivery_at,
        ], static fn ($value) => $value !== null && $value !== '');
    }

    /**
     * @param  array<string, mixed>  $prefill
     */
    private function canCreateDraftFromPrefill(array $prefill): bool
    {
        return isset($prefill['denomination'], $prefill['quantity'], $prefill['gift_send_option']);
    }

    private function orderBelongsToProduct(Order $order, Product $product): bool
    {
        if ($order->product_id) {
            return (int) $order->product_id === (int) $product->id;
        }

        return (string) $order->sku === (string) $product->sku;
    }

    private function isGiftOptionAllowedForProduct(Product $product, string $giftSendOption): bool
    {
        if ($giftSendOption === 'send_as_gift') {
            return $product->canSendAsGift();
        }

        if ($giftSendOption === 'buy_for_self') {
            return $product->canBuyForSelf();
        }

        return false;
    }

    private function assertGiftOptionAllowedForProduct(Product $product, string $giftSendOption): void
    {
        if ($this->isGiftOptionAllowedForProduct($product, $giftSendOption)) {
            return;
        }

        $message = __('error.product_unavailable');

        throw ValidationException::withMessages([
            'gift_send_option' => $message,
        ]);
    }

    private function assertThemeAllowedForGiftOption(string $giftSendOption, ?int $giftThemeId): void
    {
        if ($giftSendOption !== 'send_as_gift') {
            return;
        }

        if (! Schema::hasTable('gift_themes')) {
            throw ValidationException::withMessages([
                'gift_theme_id' => __('payments.gift_theme_invalid'),
            ]);
        }

        if (! $giftThemeId) {
            throw ValidationException::withMessages([
                'gift_theme_id' => __('payments.gift_theme_missing'),
            ]);
        }

        $isActiveTheme = GiftCardTheme::query()
            ->active()
            ->whereKey($giftThemeId)
            ->exists();

        if (! $isActiveTheme) {
            throw ValidationException::withMessages([
                'gift_theme_id' => __('payments.gift_theme_invalid'),
            ]);
        }
    }

    private function assertDeliveryOptionAllowedForGiftOption(string $giftSendOption, string $giftDeliveryOption, mixed $giftDeliveryAt): void
    {
        if ($giftSendOption !== 'send_as_gift') {
            return;
        }

        if (! in_array($giftDeliveryOption, ['send_now', 'send_later'], true)) {
            throw ValidationException::withMessages([
                'gift_delivery_option' => __('storefront.product.validation.delivery_option_required'),
            ]);
        }

        if ($giftDeliveryOption === 'send_later') {
            if (! is_string($giftDeliveryAt) || trim($giftDeliveryAt) === '') {
                throw ValidationException::withMessages([
                    'gift_delivery_at' => __('storefront.product.validation.delivery_datetime_required'),
                ]);
            }

            try {
                $deliveryAt = Carbon::parse($giftDeliveryAt);
            } catch (\Throwable) {
                throw ValidationException::withMessages([
                    'gift_delivery_at' => __('storefront.product.validation.delivery_datetime_invalid'),
                ]);
            }

            if (! $deliveryAt->isFuture()) {
                throw ValidationException::withMessages([
                    'gift_delivery_at' => __('storefront.product.validation.delivery_datetime_invalid'),
                ]);
            }

            // Optional security cap: disallow extreme future scheduling.
            if ($deliveryAt->gt(now()->addYear())) {
                throw ValidationException::withMessages([
                    'gift_delivery_at' => __('storefront.product.validation.delivery_datetime_too_far'),
                ]);
            }
        }
    }

    /**
     * API endpoint to update billing data in cache (AJAX)
     * This replaces session-based storage with cache-based storage
     *
     * @return JsonResponse
     */
    public function updateBillingCache(Request $request)
    {
        try {
            $this->rejectUnexpectedFields($request, [
                'order_id',
                'billing_name',
                'billing_email',
                'billing_tel',
                'billing_zip',
                'billing_address',
                'billing_address_two',
                'billing_city',
                'billing_state',
                'billing_country',
                'billing_gst_number',
            ]);

            $orderId = $request->input('order_id');

            if (! $orderId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order ID is required',
                ], 400);
            }

            // Verify order exists and belongs to authenticated user
            $order = Order::where('id', $orderId)
                ->where('user_id', Auth::id())
                ->first();

            if (! $order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found or unauthorized',
                ], 404);
            }

            // Store billing data in cache
            $billingData = $request->only([
                'billing_name',
                'billing_email',
                'billing_tel',
                'billing_zip',
                'billing_address',
                'billing_address_two',
                'billing_city',
                'billing_state',
                'billing_country',
                'billing_gst_number',
            ]);

            $success = CheckoutHelper::storeBillingData($orderId, $billingData);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Billing data cached successfully',
                    'ttl' => CheckoutHelper::CACHE_TTL / 60 .' minutes',
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to cache billing data',
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Failed to update billing cache', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
            ], 500);
        }
    }

    /**
     * API endpoint to retrieve billing data from cache (AJAX)
     *
     * @param  int  $orderId
     * @return JsonResponse
     */
    public function getBillingCache(Request $request, $orderId)
    {
        try {
            // Verify order exists and belongs to authenticated user
            $order = Order::where('id', $orderId)
                ->where('user_id', Auth::id())
                ->first();

            if (! $order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found or unauthorized',
                ], 404);
            }

            // Retrieve billing data from cache
            $billingData = CheckoutHelper::getBillingData($orderId);

            if ($billingData) {
                return response()->json([
                    'success' => true,
                    'data' => $billingData,
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No cached billing data found',
                ], 404);
            }

        } catch (\Exception $e) {
            Log::error('Failed to retrieve billing cache', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
            ], 500);
        }
    }

    /**
     * Store billing information to database (called from payment controller after successful payment)
     * This moves data from cache → database permanently
     *
     * @param  int  $orderId
     * @param  array|null  $billingData  Optional billing data, if null will try to load from cache
     * @return bool
     */
    public function storeBillingToDatabase($orderId, $billingData = null)
    {
        try {
            // If billing data not provided, try to load from cache
            if (! $billingData) {
                $billingData = CheckoutHelper::getBillingData($orderId);
            }

            if (! $billingData) {
                Log::warning('No billing data to store', ['order_id' => $orderId]);

                return false;
            }

            // Store to database
            $billing = Billing::updateOrCreate(
                ['order_id' => $orderId],
                $billingData
            );

            // Clear cache after successful DB storage
            CheckoutHelper::clearOrderCache($orderId);

            Log::info('Billing data moved from cache to database', [
                'order_id' => $orderId,
                'billing_id' => $billing->id,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to store billing to database', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function assertConsumerStorefrontProduct(Product $product): void
    {
        if (! $product->isListedOnConsumerStorefront()) {
            abort(404);
        }
    }

    /**
     * Reject unknown payload keys so hidden-field tampering is blocked.
     *
     * @param  list<string>  $allowedFields
     */
    private function rejectUnexpectedFields(Request $request, array $allowedFields): void
    {
        $unknown = array_values(array_diff($request->keys(), $allowedFields));
        if ($unknown === []) {
            return;
        }

        throw ValidationException::withMessages([
            'unexpected_fields' => ['Unexpected input fields detected: '.implode(', ', $unknown)],
        ]);
    }

    private function resolveCartForUser(int $userId, bool $create = true): ?Cart
    {
        if ($create) {
            return Cart::query()->firstOrCreate(['user_id' => $userId]);
        }

        return Cart::query()->where('user_id', $userId)->first();
    }
}

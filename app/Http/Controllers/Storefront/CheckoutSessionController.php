<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ProductPageController;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderBillingSnapshot;
use App\Models\Product;
use App\Models\User;
use App\Services\Checkout\CheckoutWriteService;
use App\Services\Checkout\ResolveTax;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

/**
 * Storefront checkout session mutations (draft order, billing session, cart mutations).
 *
 * Replaces unclear names like storePayNowData with semantic, stable endpoints.
 * Delegates to ProductPageController for now; we will migrate logic out incrementally.
 */
final class CheckoutSessionController extends Controller
{
    public function __construct(
        private readonly CheckoutWriteService $checkoutWriteService,
        private readonly ResolveTax $resolveTax,
        private ProductPageController $legacy,
    ) {}

    public function submitCheckout(Request $request, string $slug): mixed
    {
        $allowedKeys = [
            'denomination',
            'quantity',
            'gift_send_option',
            'receiver_name',
            'receiver_email',
            'receiver_mobile',
            'receiver_msg',
            'delivery_mode',
            'gift_theme_id',
            'gift_message_title',
            'sender_first_name',
            'gift_delivery_option',
            'gift_delivery_at',
        ];

        $this->rejectUnexpectedFields($request, $allowedKeys);

        $productQuery = Product::query();
        if (Schema::hasColumn('products', 'url')) {
            $productQuery->where('url', $slug)->orWhere('slug', $slug);
        } else {
            $productQuery->where('slug', $slug);
        }
        $product = $productQuery->firstOrFail();
        $this->assertConsumerStorefrontProduct($product);

        if ($request->gift_send_option === 'send_as_gift' && $request->receiver_mobile) {
            $recipient = User::query()->whereMobile((string) $request->receiver_mobile)->first();
            if ($recipient && ! $recipient->can_receive_gifts) {
                Log::warning('Attempted to send gift to self', [
                    'sender_id' => Auth::id(),
                    'recipient_mobile' => $request->receiver_mobile,
                ]);

                if ($request->header('X-Inertia')) {
                    return Inertia::render('Error', [
                        'status' => 403,
                        'message' => 'This recipient is not eligible to receive gifts.',
                        'details' => [
                            'blockType' => 'recipient',
                            'phone' => $request->receiver_mobile,
                            'reason' => $recipient->restriction_reason,
                        ],
                    ])->toResponse($request)->setStatusCode(403);
                }

                return response('This recipient is not eligible to receive gifts.', 403, [
                    'Content-Type' => 'text/plain; charset=UTF-8',
                ]);
            }
        }

        $rules = [
            'denomination' => [
                'required',
                function ($attribute, $value, $fail) use ($product) {
                    $priceData = $product->resolveStorefrontPrice();
                    $priceType = strtoupper((string) ($priceData['type'] ?? 'RANGE'));

                    if ($priceType === 'SLAB') {
                        $denominations = $priceData['denominations'] ?? [];
                        $valueFloat = (float) $value;
                        $allowed = array_map('floatval', $denominations);
                        if (! in_array($valueFloat, $allowed, true)) {
                            $fail('Invalid denomination value. Allowed values are: '.implode(', ', array_map(fn ($d) => '₹'.(float) $d, $allowed)));
                        }
                        return;
                    }

                    $minPrice = isset($priceData['min']) ? (float) $priceData['min'] : 0.0;
                    $maxPrice = isset($priceData['max']) ? (float) $priceData['max'] : 0.0;

                    if ($minPrice <= 0 || $maxPrice <= 0 || $maxPrice < $minPrice) {
                        $fail('Price range not configured for this product.');
                        return;
                    }

                    $valueFloat = (float) $value;
                    if ($valueFloat < $minPrice || $valueFloat > $maxPrice) {
                        $fail("The denomination must be between ₹{$minPrice} and ₹{$maxPrice}.");
                    }
                },
            ],
            'quantity' => 'required|integer|min:1|max:10',
            'gift_send_option' => 'required|in:send_as_gift,buy_for_self',
            'receiver_name' => 'nullable|required_if:gift_send_option,send_as_gift|string|max:255',
            'receiver_email' => 'nullable|required_if:gift_send_option,send_as_gift|email',
            'receiver_mobile' => 'nullable|required_if:gift_send_option,send_as_gift|digits:10',
            'receiver_msg' => 'nullable|required_if:gift_send_option,send_as_gift|string|max:500',
            'gift_theme_id' => 'nullable|required_if:gift_send_option,send_as_gift|integer|exists:gift_themes,id',
            'gift_message_title' => 'nullable|required_if:gift_send_option,send_as_gift|string|max:120',
            'sender_first_name' => 'nullable|required_if:gift_send_option,send_as_gift|string|max:120',
            'gift_delivery_option' => 'nullable|required_if:gift_send_option,send_as_gift|string|in:send_now,send_later',
            'gift_delivery_at' => 'nullable|required_if:gift_delivery_option,send_later|date|after:now',
        ];

        $request->merge(['delivery_mode' => 'both']);

        $validator = Validator::make($request->only(array_keys($rules)), $rules);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $this->assertGiftOptionAllowedForProduct($product, (string) $request->gift_send_option);
        $this->assertThemeAllowedForGiftOption(
            (string) $request->gift_send_option,
            $request->filled('gift_theme_id') ? (int) $request->gift_theme_id : null
        );
        $this->assertDeliveryOptionAllowedForGiftOption(
            (string) $request->gift_send_option,
            (string) $request->gift_delivery_option,
            $request->gift_delivery_at
        );

        $userId = Auth::id();

        try {
            $order = $this->checkoutWriteService->createOrRefreshDraftOrder($product, (int) $userId, $validator->validated());
            // Regenerate session ID for safety, then persist checkout context.
            $request->session()->regenerate();
            session(['checkout_order_id' => $order->id]);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $e) {
            Log::error('Failed to create order', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'sku' => $product->sku ?? null,
                'ip_address' => $request->ip(),
            ]);

            return back()->withErrors(['message' => __('payments.order_create_failed')])->withInput();
        }

        return redirect()->route('checkoutPage', ['slug' => $slug]);
    }

    public function updateBillingDetails(Request $request): mixed
    {
        $allowedKeys = [
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
        ];

        $this->rejectUnexpectedFields($request, $allowedKeys);

        $orderId = (int) session('checkout_order_id', 0);
        if ($orderId <= 0) {
            return response()->json([
                'message' => 'No active checkout session. Complete the checkout step first.',
            ], 422);
        }

        $order = Order::query()
            ->where('id', $orderId)
            ->where('user_id', Auth::id())
            ->first();

        if (! $order) {
            return response()->json([
                'message' => 'Order not found or access denied.',
            ], 404);
        }

        $validated = $request->validate([
            'billing_name' => 'required|string|max:255',
            'billing_email' => 'required|email|max:255',
            'billing_tel' => 'nullable|string|max:50',
            'billing_zip' => 'nullable|string|max:20',
            'billing_address' => 'nullable|string|max:255',
            'billing_address_two' => 'nullable|string|max:255',
            'billing_city' => 'nullable|string|max:100',
            'billing_state' => 'nullable|string|max:100',
            'billing_country' => 'nullable|string|max:100',
            'billing_gst_number' => 'nullable|string|max:50',
        ]);

        OrderBillingSnapshot::query()->updateOrCreate(
            ['order_id' => $order->id],
            [
                'full_name' => (string) ($validated['billing_name'] ?? ''),
                'email' => (string) ($validated['billing_email'] ?? ''),
                'phone' => (string) ($validated['billing_tel'] ?? ''),
                'line1' => (string) ($validated['billing_address'] ?? ''),
                'line2' => (string) ($validated['billing_address_two'] ?? ''),
                'city' => (string) ($validated['billing_city'] ?? ''),
                'state' => (string) ($validated['billing_state'] ?? ''),
                'postal_code' => (string) ($validated['billing_zip'] ?? ''),
                'country' => (string) ($validated['billing_country'] ?? 'IN'),
                'gst_number' => (string) ($validated['billing_gst_number'] ?? ''),
            ]
        );

        // Phase 3: tax depends on billing jurisdiction; re-resolve after billing updates.
        $this->resolveTax->resolveAndPersist($order->fresh());

        return response()->json(['message' => __('responses.OK')]);
    }

    public function addToCart(Request $request, string $slug): mixed
    {
        $allowedKeys = [
            'denomination',
            'quantity',
            'gift_send_option',
            'receiver_name',
            'receiver_email',
            'receiver_mobile',
            'receiver_msg',
            'gift_theme_id',
            'gift_message_title',
            'sender_first_name',
            'gift_delivery_option',
            'gift_delivery_at',
        ];

        $this->rejectUnexpectedFields($request, $allowedKeys);

        $productQuery = Product::query();
        if (Schema::hasColumn('products', 'url')) {
            $productQuery->where('url', $slug)->orWhere('slug', $slug);
        } else {
            $productQuery->where('slug', $slug);
        }
        $product = $productQuery->firstOrFail();
        $this->assertConsumerStorefrontProduct($product);

        $rules = [
            'denomination' => [
                'required',
                function ($attribute, $value, $fail) use ($product) {
                    $priceData = $product->resolveStorefrontPrice();
                    $priceType = strtoupper((string) ($priceData['type'] ?? 'RANGE'));

                    if ($priceType === 'SLAB') {
                        $denominations = $priceData['denominations'] ?? [];
                        $valueFloat = (float) $value;
                        $allowed = array_map('floatval', $denominations);
                        if (! in_array($valueFloat, $allowed, true)) {
                            $fail('Invalid denomination value. Allowed values are: '.implode(', ', array_map(fn ($d) => '₹'.(float) $d, $allowed)));
                        }

                        return;
                    }

                    $minPrice = isset($priceData['min']) ? (float) $priceData['min'] : 0.0;
                    $maxPrice = isset($priceData['max']) ? (float) $priceData['max'] : 0.0;

                    if ($minPrice <= 0 || $maxPrice <= 0 || $maxPrice < $minPrice) {
                        $fail('Price range not configured for this product.');
                        return;
                    }

                    $valueFloat = (float) $value;
                    if ($valueFloat < $minPrice || $valueFloat > $maxPrice) {
                        $fail("The denomination must be between ₹{$minPrice} and ₹{$maxPrice}.");
                    }
                },
            ],
            'quantity' => 'required|integer|min:1|max:10',
            'gift_send_option' => 'required|in:send_as_gift,buy_for_self',
            'receiver_name' => 'nullable|required_if:gift_send_option,send_as_gift|string|max:255',
            'receiver_email' => 'nullable|required_if:gift_send_option,send_as_gift|email|max:255',
            'receiver_mobile' => 'nullable|required_if:gift_send_option,send_as_gift|digits:10',
            'receiver_msg' => 'nullable|required_if:gift_send_option,send_as_gift|string|max:500',
            'gift_theme_id' => 'nullable|required_if:gift_send_option,send_as_gift|integer',
            'gift_message_title' => 'nullable|required_if:gift_send_option,send_as_gift|string|max:120',
            'sender_first_name' => 'nullable|required_if:gift_send_option,send_as_gift|string|max:120',
            'gift_delivery_option' => 'nullable|required_if:gift_send_option,send_as_gift|string|in:send_now,send_later',
            'gift_delivery_at' => 'nullable|required_if:gift_delivery_option,send_later|date|after:now',
        ];

        $validated = $request->validate($rules);

        $this->assertGiftOptionAllowedForProduct($product, (string) $validated['gift_send_option']);

        $this->assertThemeAllowedForGiftOption(
            (string) $validated['gift_send_option'],
            isset($validated['gift_theme_id']) ? (int) $validated['gift_theme_id'] : null
        );

        $this->assertDeliveryOptionAllowedForGiftOption(
            (string) $validated['gift_send_option'],
            (string) ($validated['gift_delivery_option'] ?? ''),
            $validated['gift_delivery_at'] ?? null
        );

        $lockedProduct = Product::query()->whereKey($product->id)->firstOrFail();
        $priceData = $lockedProduct->resolveStorefrontPrice();
        $priceType = strtoupper((string) ($priceData['type'] ?? 'RANGE'));
        $denomination = (float) $validated['denomination'];
        $quantity = (int) $validated['quantity'];

        if ($priceType === 'SLAB') {
            $denominations = array_map('floatval', (array) ($priceData['denominations'] ?? []));
            if (! in_array((float) $denomination, $denominations, true)) {
                throw ValidationException::withMessages([
                    'denomination' => 'Invalid denomination value.',
                ]);
            }
        } else {
            $minPrice = isset($priceData['min']) ? (float) $priceData['min'] : 0.0;
            $maxPrice = isset($priceData['max']) ? (float) $priceData['max'] : 0.0;
            if ($minPrice <= 0 || $maxPrice <= 0 || $maxPrice < $minPrice) {
                throw ValidationException::withMessages([
                    'denomination' => 'Price range not configured for this product.',
                ]);
            }
            if ($denomination < $minPrice || $denomination > $maxPrice) {
                throw ValidationException::withMessages([
                    'denomination' => "The denomination must be between ₹{$minPrice} and ₹{$maxPrice}.",
                ]);
            }
        }

        $lineTotal = $denomination * $quantity;
        $cart = $this->resolveCartForUser((int) Auth::id());

        $cart->items()->create([
            'product_id' => $lockedProduct->id,
            'gift_theme_id' => ($validated['gift_send_option'] ?? '') === 'send_as_gift' && isset($validated['gift_theme_id'])
                ? (int) $validated['gift_theme_id']
                : null,
            'product_slug' => (string) $slug,
            'sku' => (string) $lockedProduct->sku,
            'product_name' => (string) $lockedProduct->name,
            'gift_send_option' => (string) $validated['gift_send_option'],
            'gift_message_title' => ($validated['gift_send_option'] ?? '') === 'send_as_gift' ? (string) ($validated['gift_message_title'] ?? '') : null,
            'gift_delivery_option' => ($validated['gift_send_option'] ?? '') === 'send_as_gift' ? (string) ($validated['gift_delivery_option'] ?? 'send_now') : null,
            'gift_delivery_at' => (($validated['gift_send_option'] ?? '') === 'send_as_gift' && isset($validated['gift_delivery_at']))
                ? (string) $validated['gift_delivery_at']
                : null,
            'sender_first_name' => ($validated['gift_send_option'] ?? '') === 'send_as_gift' ? (string) ($validated['sender_first_name'] ?? '') : null,
            'receiver_name' => ($validated['gift_send_option'] ?? '') === 'send_as_gift' ? (string) ($validated['receiver_name'] ?? '') : null,
            'receiver_email' => ($validated['gift_send_option'] ?? '') === 'send_as_gift' ? (string) ($validated['receiver_email'] ?? '') : null,
            'receiver_mobile' => ($validated['gift_send_option'] ?? '') === 'send_as_gift' ? (string) ($validated['receiver_mobile'] ?? '') : null,
            'receiver_msg' => ($validated['gift_send_option'] ?? '') === 'send_as_gift' ? (string) ($validated['receiver_msg'] ?? '') : null,
            'denomination' => $denomination,
            'quantity' => $quantity,
            'line_total' => $lineTotal,
        ]);

        return back()->with('success', 'Added to cart successfully.');
    }

    public function removeFromCart(Request $request): mixed
    {
        $this->rejectUnexpectedFields($request, ['cart_item_id']);

        $validated = $request->validate([
            'cart_item_id' => 'required|integer',
        ]);

        $cart = $this->resolveCartForUser((int) Auth::id(), false);
        if ($cart) {
            $cart->items()
                ->whereKey((int) $validated['cart_item_id'])
                ->delete();
        }

        return back()->with('success', 'Removed from cart.');
    }

    public function clearCart(Request $request): mixed
    {
        $this->rejectUnexpectedFields($request, []);

        $cart = $this->resolveCartForUser((int) Auth::id(), false);
        if ($cart) {
            $cart->items()->delete();
        }

        return back()->with('success', 'Cart cleared.');
    }

    public function saveGiftCheckoutDraft(Request $request): JsonResponse
    {
        $allowedKeys = [
            'gift_send_option',
            'receiver_name',
            'receiver_email',
            'receiver_mobile',
            'receiver_msg',
            'gift_theme_id',
            'gift_message_title',
            'sender_first_name',
            'delivery_mode',
        ];

        $this->rejectUnexpectedFields($request, $allowedKeys);

        if (! $request->has('delivery_mode')) {
            $request->merge(['delivery_mode' => 'both']);
        }

        $validated = $request->validate([
            'gift_send_option' => 'required|string|in:send_as_gift,buy_for_self',
            'receiver_name' => 'nullable|required_if:gift_send_option,send_as_gift|string|max:255',
            'receiver_email' => 'nullable|required_if:gift_send_option,send_as_gift|email|max:255',
            'receiver_mobile' => 'nullable|required_if:gift_send_option,send_as_gift|digits:10',
            'receiver_msg' => 'nullable|required_if:gift_send_option,send_as_gift|string|max:500',
            'gift_theme_id' => [
                'nullable',
                'required_if:gift_send_option,send_as_gift',
                'integer',
                Rule::exists('gift_themes', 'id')->where(static fn ($q) => $q->where('is_active', 1)),
            ],
            'gift_message_title' => 'nullable|required_if:gift_send_option,send_as_gift|string|max:120',
            'sender_first_name' => 'nullable|required_if:gift_send_option,send_as_gift|string|max:120',
            'delivery_mode' => 'required_if:gift_send_option,send_as_gift|string|in:both,email,sms',
        ]);

        session([
            'gift_card_form' => $validated,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Gift checkout draft saved successfully',
        ]);
    }

    private function rejectUnexpectedFields(Request $request, array $allowedKeys): void
    {
        $allowed = array_fill_keys(array_merge($allowedKeys, ['_token']), true);
        $incoming = $request->keys();
        $unexpected = array_values(array_diff($incoming, array_keys($allowed)));

        if ($unexpected !== []) {
            abort(422, 'Unexpected input fields: '.implode(', ', $unexpected));
        }
    }

    private function resolveCartForUser(int $userId, bool $create = true): ?Cart
    {
        if ($create) {
            return Cart::query()->firstOrCreate(['user_id' => $userId]);
        }

        return Cart::query()->where('user_id', $userId)->first();
    }

    private function assertConsumerStorefrontProduct(Product $product): void
    {
        if (! $product->isListedOnConsumerStorefront()) {
            abort(404);
        }
    }

    private function assertGiftOptionAllowedForProduct(Product $product, string $giftSendOption): void
    {
        $allowed = ['buy_for_self'];

        if ($product->can_send_as_gift ?? true) {
            $allowed[] = 'send_as_gift';
        }

        if (! in_array($giftSendOption, $allowed, true)) {
            throw ValidationException::withMessages([
                'gift_send_option' => __('storefront.product.validation.gift_send_option_invalid'),
            ]);
        }
    }

    private function assertThemeAllowedForGiftOption(string $giftSendOption, ?int $giftThemeId): void
    {
        if ($giftSendOption !== 'send_as_gift') {
            return;
        }

        // Defer deep theme validation to ProductPageController domain logic later.
        if (! $giftThemeId) {
            throw ValidationException::withMessages([
                'gift_theme_id' => __('payments.gift_theme_missing'),
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
        }
    }
}


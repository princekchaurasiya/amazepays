<?php

namespace App\Http\Controllers;

use App\Helpers\CheckoutHelper;
use App\Helpers\ProductHelper;
use App\Models\Billing;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\GiftCardTheme;
use App\Models\Order;
use App\Models\OrderSummary;
use App\Models\Product;
use App\Models\UnlimitPayment;
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
        $formData = $request->only([
            'denomination', 'quantity', 'gift_send_option', 'receiver_name',
            'receiver_email', 'receiver_mobile', 'receiver_msg',
            'gift_theme_id', 'gift_message_title', 'sender_first_name', 'gift_delivery_option', 'gift_delivery_at',
        ]);
        session(['giftCardFormValues' => $formData]);

        return response()->json(['status' => 'success']);
    }

    public function storePayNowData(Request $request, $slug)
    {
        // Handle GET request - show checkout form
        if ($request->isMethod('get')) {
            return $this->showCheckoutForm($request, $slug);
        }
        $this->rejectUnexpectedFields($request, self::GIFT_CHECKOUT_INPUT_KEYS);

        // Handle POST request - process order and payment

        // Fetch product by slug
        $product = Product::where('url', $slug)->firstOrFail();
        $this->assertConsumerStorefrontProduct($product);

        // If sending as a gift, check if recipient is blocked
        if ($request->gift_send_option === 'send_as_gift' && $request->receiver_mobile) {
            $recipient = User::where('mobile', $request->receiver_mobile)->first();
            if ($recipient && ! $recipient->can_receive_gifts) {
                Log::warning('Attempted to send gift to self', [
                    'sender_id' => Auth::id(),
                    'recipient_mobile' => $request->receiver_mobile,
                ]);

                return response()->view('errors.self-gift', [
                    'blockType' => 'recipient',
                    'phone' => $request->receiver_mobile,
                    'reason' => $recipient->restriction_reason,
                ], 403);
            }
        }

        // Price is already decoded by model accessor (uses ProductHelper internally)

        // Validation rules for the form
        $rules = [
            'denomination' => [
                'required',
                function ($attribute, $value, $fail) use ($product) {

                    // If $product->price is an object, convert it to an array
                    $priceData = (array) $product->price;

                    // Check for valid price data
                    if (! is_array($priceData)) {
                        Log::warning('Invalid price format', ['price' => $product->price]);
                        $fail('Invalid product price configuration.');

                        return;
                    }

                    // Default to 'RANGE' if type is missing
                    $priceType = $priceData['type'] ?? 'RANGE';

                    // Now handle SLAB or RANGE validation
                    if ($priceType === 'SLAB') {
                        // Validate SLAB type denominations
                        $denominations = $priceData['denominations'] ?? [];
                        if (! in_array((string) $value, $denominations)) {
                            Log::warning('Invalid SLAB denomination', [
                                'denomination' => $value,
                                'allowed' => $denominations,
                            ]);
                            $fail('Invalid denomination value. Allowed values are: '.implode(', ', $denominations));
                        }
                    } elseif ($priceType === 'RANGE') {
                        // Validate RANGE type price range
                        $minPrice = $priceData['min'] ?? $product->minPrice;
                        $maxPrice = $priceData['max'] ?? $product->maxPrice;

                        // Default to the range if missing
                        if ($minPrice === null || $maxPrice === null) {
                            Log::warning('Missing min/max values for RANGE type, using default min/max', [
                                'minPrice' => $minPrice,
                                'maxPrice' => $maxPrice,
                                'product_id' => $product->id,
                            ]);
                        }

                        if ($value < $minPrice || $value > $maxPrice) {
                            Log::warning('Denomination out of RANGE', [
                                'denomination' => $value,
                                'min' => $minPrice,
                                'max' => $maxPrice,
                            ]);
                            $fail("The denomination must be between ₹{$minPrice} and ₹{$maxPrice}.");
                        }
                    } else {
                        Log::warning('Unknown price type', ['priceType' => $priceType]);
                        $fail('Invalid price configuration.');
                    }
                },
            ],
            'quantity' => 'required|integer|min:1|max:10',
            'gift_send_option' => 'required|in:send_as_gift,buy_for_self',
            'receiver_name' => 'nullable|required_if:gift_send_option,send_as_gift|string|max:255',
            'receiver_email' => 'nullable|required_if:gift_send_option,send_as_gift|email',
            'receiver_mobile' => 'nullable|required_if:gift_send_option,send_as_gift|digits:10',
            'receiver_msg' => 'nullable|required_if:gift_send_option,send_as_gift|string|max:500',
            'gift_theme_id' => 'nullable|required_if:gift_send_option,send_as_gift|integer|exists:gift_card_themes,id',
            'gift_message_title' => 'nullable|required_if:gift_send_option,send_as_gift|string|max:120',
            'sender_first_name' => 'nullable|required_if:gift_send_option,send_as_gift|string|max:120',
            'gift_delivery_option' => 'nullable|required_if:gift_send_option,send_as_gift|string|in:send_now,send_later',
            'gift_delivery_at' => 'nullable|required_if:gift_delivery_option,send_later|date|after:now',
        ];

        $request->merge(['delivery_mode' => 'both']);

        $validator = Validator::make($request->only(array_keys($rules)), $rules);
        if ($validator->fails()) {
            Log::warning('Validation failed', ['errors' => $validator->errors()]);

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

        $checkoutData = $request->only(self::GIFT_CHECKOUT_INPUT_KEYS);
        $userId = Auth::id();

        try {
            $order = $this->checkoutWriteService->createOrRefreshDraftOrder($product, (int) $userId, $validator->validated());
            session(['checkout_order_id' => $order->id]);
            $request->session()->regenerate();
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $e) {
            Log::error('❌ Failed to create order', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $userId,
                'sku' => $product->sku ?? null,
                'ip_address' => $request->ip(),
            ]);

            return back()->withErrors(['message' => __('payments.order_create_failed')])->withInput();
        }

        return redirect()->route('checkoutPage', ['slug' => $slug]);
    }

    public function updateSessionData(Request $request)
    {
        $this->rejectUnexpectedFields($request, [
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

        $orderId = session('checkout_order_id');
        if (! $orderId) {
            return response()->json([
                'message' => 'No active checkout session. Complete the checkout step first.',
            ], 422);
        }

        $order = Order::where('id', $orderId)
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

        $billingKeys = [
            'billing_name', 'billing_email', 'billing_tel', 'billing_zip',
            'billing_address', 'billing_address_two', 'billing_city',
            'billing_state', 'billing_country', 'billing_gst_number',
        ];
        $billingOnly = array_intersect_key($validated, array_flip($billingKeys));

        if (Schema::hasColumn('billings', 'order_id')) {
            Billing::updateOrCreate(
                ['order_id' => $order->id],
                $billingOnly
            );
        } else {
            $billing = new Billing;
            $billing->fill($billingOnly);
            $billing->save();
        }

        return response()->json(['message' => __('responses.OK')]);
    }

    public function addToCart(Request $request, $slug)
    {
        $this->rejectUnexpectedFields($request, self::GIFT_CHECKOUT_INPUT_KEYS);

        $product = Product::where('url', $slug)->firstOrFail();
        $this->assertConsumerStorefrontProduct($product);

        $rules = [
            'denomination' => [
                'required',
                function ($attribute, $value, $fail) use ($product) {
                    $priceData = (array) $product->price;
                    $priceType = strtoupper((string) ($priceData['type'] ?? 'RANGE'));

                    if ($priceType === 'SLAB') {
                        $denominations = $priceData['denominations'] ?? [];
                        if (! in_array((string) $value, $denominations, true)) {
                            $fail('Invalid denomination value. Allowed values are: '.implode(', ', $denominations));
                        }

                        return;
                    }

                    $minPrice = isset($priceData['min']) ? (float) $priceData['min'] : (float) ($product->minPrice ?? 0);
                    $maxPrice = isset($priceData['max']) ? (float) $priceData['max'] : (float) ($product->maxPrice ?? 0);

                    if ($value < $minPrice || $value > $maxPrice) {
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

        $lockedProduct = Product::where('id', $product->id)->firstOrFail();
        $priceData = (array) $lockedProduct->price;
        $priceType = strtoupper((string) ($priceData['type'] ?? 'RANGE'));
        $denomination = (float) $validated['denomination'];
        $quantity = (int) $validated['quantity'];

        if ($priceType === 'SLAB') {
            $denominations = array_map(static fn ($value): string => (string) $value, (array) ($priceData['denominations'] ?? []));
            if (! in_array((string) $denomination, $denominations, true)) {
                throw ValidationException::withMessages([
                    'denomination' => 'Invalid denomination value.',
                ]);
            }
        } else {
            $minPrice = isset($priceData['min']) ? (float) $priceData['min'] : (float) ($lockedProduct->minPrice ?? 0);
            $maxPrice = isset($priceData['max']) ? (float) $priceData['max'] : (float) ($lockedProduct->maxPrice ?? 0);
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

    public function clearCart(Request $request)
    {
        $this->rejectUnexpectedFields($request, []);

        $cart = $this->resolveCartForUser((int) Auth::id(), false);
        if ($cart) {
            $cart->items()->delete();
        }

        return back()->with('success', 'Cart cleared.');
    }

    public function showCheckoutForm(Request $request, $slug)
    {
        Log::info('showCheckoutForm called for slug: '.$slug);
        $sessionOrderId = (int) session('checkout_order_id');
        $order = $this->checkoutReadService->findOrderForUser($sessionOrderId > 0 ? $sessionOrderId : null, (int) Auth::id());
        if (! $order && $sessionOrderId > 0) {
            session()->forget('checkout_order_id');
        }
        $order ??= new Order;

        // Fetch product by slug
        $product = Product::where('url', $slug)->firstOrFail();
        $this->assertConsumerStorefrontProduct($product);

        if ($order?->id && ! $this->orderBelongsToProduct($order, $product)) {
            session()->forget('checkout_order_id');
            $order = new Order;
        }

        $prefillFromCartItem = $this->checkoutReadService->extractCheckoutPrefillFromCartItem($request, $product, (int) Auth::id());
        $prefillFromQuery = $this->checkoutReadService->extractCheckoutPrefillFromQuery($request, $product);
        $prefillFromSource = $prefillFromCartItem !== [] ? $prefillFromCartItem : $prefillFromQuery;
        $hasQueryParams = $request->query->count() > 0;
        if ($this->canCreateDraftFromPrefill($prefillFromSource)) {
            try {
                $order = $this->checkoutWriteService->createOrRefreshDraftOrder($product, (int) Auth::id(), [
                    'denomination' => (float) $prefillFromSource['denomination'],
                    'quantity' => (int) $prefillFromSource['quantity'],
                    'gift_send_option' => (string) $prefillFromSource['gift_send_option'],
                    'receiver_name' => $prefillFromSource['receiver_name'] ?? null,
                    'receiver_email' => $prefillFromSource['receiver_email'] ?? null,
                    'receiver_mobile' => $prefillFromSource['receiver_mobile'] ?? null,
                    'receiver_msg' => $prefillFromSource['receiver_msg'] ?? null,
                    'gift_theme_id' => $prefillFromSource['gift_theme_id'] ?? null,
                    'gift_message_title' => $prefillFromSource['gift_message_title'] ?? null,
                    'sender_first_name' => $prefillFromSource['sender_first_name'] ?? null,
                    'gift_delivery_option' => $prefillFromSource['gift_delivery_option'] ?? null,
                    'gift_delivery_at' => $prefillFromSource['gift_delivery_at'] ?? null,
                ]);
                session(['checkout_order_id' => $order->id]);
            } catch (\Throwable $e) {
                Log::warning('Checkout draft recovery skipped', [
                    'slug' => $slug,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        if ($hasQueryParams) {
            return redirect()->route('checkoutPage', ['slug' => $slug]);
        }

        // Currency and images are already decoded by model accessors
        $product['currency'] = $product->currency;
        $product['images'] = $product->images;
        $checkoutData = $this->resolveCheckoutData($order, $prefillFromSource);
        $product['productData'] = array_intersect_key($checkoutData, array_flip(self::GIFT_CHECKOUT_INPUT_KEYS));
        Log::info('Checkout data resolved', $checkoutData);

        // Try to load cached billing data
        $cachedBilling = $order->id
            ? CheckoutHelper::getBillingData((int) $order->id)
            : null;
        $requiredBillingFields = $this->billingRequirements->requiredFields('upi', (string) ($product->source_provider ?? ''));
        $billingSnapshot = $this->checkoutReadService->buildBillingSnapshot(
            $order->id ? $order : null,
            $cachedBilling,
            Auth::user(),
            $requiredBillingFields
        );

        // Remove payment gateway calls from GET request - these should only be called on form submission
        // Payment gateway initialization will be handled when user clicks submit button

        return Inertia::render('Checkout/Index', [
            'product' => $product,
            'checkoutData' => $checkoutData,
            'order' => $this->checkoutOrderPayloadFactory->make($order),
            'cachedBilling' => $cachedBilling,
            'billingSnapshot' => $billingSnapshot['snapshot'],
            'billingReady' => $billingSnapshot['ready'],
            'billingMissingFields' => $billingSnapshot['missing'],
            'billingRequiredFields' => $requiredBillingFields,
            'billingRequiredFieldLabels' => $this->billingRequirements->labels($requiredBillingFields),
            'billingRequirementContext' => [
                'payment_method' => 'upi',
                'provider' => (string) ($product->source_provider ?? 'unknown'),
            ],
            'slug' => $slug,
        ]);
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

        $message = __('responses.PRODUCT_UNAVAILABLE');

        throw ValidationException::withMessages([
            'gift_send_option' => $message,
        ]);
    }

    private function assertThemeAllowedForGiftOption(string $giftSendOption, ?int $giftThemeId): void
    {
        if ($giftSendOption !== 'send_as_gift') {
            return;
        }

        if (! Schema::hasTable('gift_card_themes')) {
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
        $unknown = array_values(array_diff(array_keys($request->all()), $allowedFields));
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

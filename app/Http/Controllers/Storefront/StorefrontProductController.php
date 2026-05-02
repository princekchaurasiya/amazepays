<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Helpers\CheckoutHelper;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ProductPageController;
use App\Models\Order;
use App\Models\OrderBillingSnapshot;
use App\Models\Product;
use App\Services\Checkout\CartResolver;
use App\Services\Checkout\CheckoutOrderPayloadFactory;
use App\Services\Checkout\CheckoutReadService;
use App\Services\Checkout\CheckoutWriteService;
use App\Support\BillingRequirementResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

/**
 * Storefront product + cart read views (Inertia).
 *
 * This controller exists to replace the generic/overloaded ProductPageController.
 * For now it delegates to the legacy controller to avoid a risky move-big-code change.
 */
final class StorefrontProductController extends Controller
{
    /** @var list<string> */
    private const GIFT_CHECKOUT_INPUT_KEYS = [
        'denomination', 'quantity', 'gift_send_option', 'receiver_name',
        'receiver_email', 'receiver_mobile', 'receiver_msg', 'delivery_mode',
        'gift_theme_id', 'gift_message_title', 'sender_first_name', 'gift_delivery_option', 'gift_delivery_at',
    ];

    public function __construct(
        private readonly BillingRequirementResolver $billingRequirements,
        private readonly CheckoutReadService $checkoutReadService,
        private readonly CheckoutWriteService $checkoutWriteService,
        private readonly CheckoutOrderPayloadFactory $checkoutOrderPayloadFactory,
        private readonly CartResolver $cartResolver,
        private ProductPageController $legacy,
    ) {}

    public function showCheckout(Request $request, string $slug): mixed
    {
        Log::info('showCheckout called for slug: '.$slug);

        $sessionOrderId = (int) session('checkout_order_id');
        $order = $this->checkoutReadService->findOrderForUser($sessionOrderId > 0 ? $sessionOrderId : null, (int) Auth::id());
        if (! $order && $sessionOrderId > 0) {
            session()->forget('checkout_order_id');
        }
        $order ??= new Order;

        $productQuery = Product::query();
        if (Schema::hasColumn('products', 'url')) {
            $productQuery->where('url', $slug)->orWhere('slug', $slug);
        } else {
            $productQuery->where('slug', $slug);
        }
        $product = $productQuery->firstOrFail();
        $this->assertConsumerStorefrontProduct($product);

        if ($order?->id && ! $this->orderBelongsToProduct($order, $product)) {
            session()->forget('checkout_order_id');
            $order = new Order;
        }

        $prefillFromCartItem = $this->checkoutReadService->extractCheckoutPrefillFromCartItem($request, $product, (int) Auth::id());
        $prefillFromLatestCart = $prefillFromCartItem === []
            ? $this->checkoutReadService->extractCheckoutPrefillFromLatestCartLine($product, $this->cartResolver->resolveForAuthenticatedPrefill($request))
            : [];
        $prefillFromQuery = $this->checkoutReadService->extractCheckoutPrefillFromQuery($request, $product);
        $prefillFromSource = $prefillFromCartItem !== []
            ? $prefillFromCartItem
            : ($prefillFromLatestCart !== [] ? $prefillFromLatestCart : $prefillFromQuery);

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

        $product['currency'] = $product->currency;
        $product['images'] = $product->images;

        $checkoutData = $this->resolveCheckoutData($order, $prefillFromSource);
        $product['productData'] = array_intersect_key($checkoutData, array_flip(self::GIFT_CHECKOUT_INPUT_KEYS));

        $cachedBilling = $order->id
            ? CheckoutHelper::getBillingData((int) $order->id)
            : null;
        $provider = (string) ($product->source_provider ?? '');

        $methodToggles = (array) config('storefront.checkout_payment_methods', []);
        $enabledMethods = collect(['ccavenue', 'razorpay', 'unlimit'])
            ->filter(fn (string $m) => (bool) ($methodToggles[$m] ?? false))
            ->values()
            ->all();

        // Always keep at least Razorpay enabled to prevent empty checkout screen.
        if ($enabledMethods === []) {
            $enabledMethods = ['razorpay'];
        }

        $requiredBillingFieldsByMethod = collect($enabledMethods)
            ->mapWithKeys(fn (string $method) => [$method => $this->billingRequirements->requiredFields($method, $provider)])
            ->all();

        $billingSnapshots = collect($requiredBillingFieldsByMethod)
            ->map(fn (array $required) => $this->checkoutReadService->buildBillingSnapshot(
                $order->id ? $order : null,
                $cachedBilling,
                Auth::user(),
                $required
            ))
            ->all();

        // If billing is not ready for any enabled method, redirect to profile to complete details,
        // then come back to this checkout page.
        $hasAnyReady = collect($enabledMethods)->contains(fn (string $m) => (bool) ($billingSnapshots[$m]['ready'] ?? false));
        if (! $hasAnyReady) {
            // Use relative path to avoid open-redirects and keep profile return_to safe.
            $returnTo = route('checkoutPage', ['slug' => $slug], false);

            return redirect()
                ->route('profile', ['return_to' => $returnTo])
                ->with('warning', 'Please complete your profile details to continue checkout.');
        }

        // Persist immutable billing snapshot onto the order (Phase-3 schema).
        // This is critical for downstream fulfilment (Woohoo) because orders do not carry billing columns.
        if ($order->id && Schema::hasTable('order_billing_snapshots')) {
            $firstReadyMethod = collect($enabledMethods)->first(fn (string $m) => (bool) ($billingSnapshots[$m]['ready'] ?? false)) ?? $enabledMethods[0];
            $snap = (array) (($billingSnapshots[$firstReadyMethod]['snapshot'] ?? []) ?: []);

            OrderBillingSnapshot::query()->updateOrCreate(
                ['order_id' => (int) $order->id],
                [
                    'full_name' => (string) ($snap['billing_name'] ?? 'Customer'),
                    'email' => (string) ($snap['billing_email'] ?? ''),
                    'phone' => (string) ($snap['billing_tel'] ?? ''),
                    'line1' => (string) ($snap['billing_address'] ?? '-'),
                    'line2' => (string) ($snap['billing_address_two'] ?? ''),
                    'city' => (string) ($snap['billing_city'] ?? '-'),
                    'state' => (string) ($snap['billing_state'] ?? '-'),
                    'postal_code' => (string) ($snap['billing_zip'] ?? '000000'),
                    'country' => (string) ($snap['billing_country'] ?? 'IN'),
                    'gst_number' => (string) ($snap['billing_gst_number'] ?? ''),
                ]
            );
        }

        return Inertia::render('Checkout/Index', [
            'product' => $product,
            'checkoutData' => $checkoutData,
            'order' => $this->checkoutOrderPayloadFactory->make($order),
            'cachedBilling' => $cachedBilling,
            'enabledPaymentMethods' => $enabledMethods,
            'billingSnapshot' => $billingSnapshots['razorpay']['snapshot'] ?? ($billingSnapshots[$enabledMethods[0]]['snapshot'] ?? []),
            'billingRequiredFieldsByMethod' => $requiredBillingFieldsByMethod,
            'billingMissingFieldsByMethod' => [
                'ccavenue' => $billingSnapshots['ccavenue']['missing'] ?? [],
                'razorpay' => $billingSnapshots['razorpay']['missing'] ?? [],
                'unlimit' => $billingSnapshots['unlimit']['missing'] ?? [],
            ],
            'billingReadyByMethod' => [
                'ccavenue' => (bool) ($billingSnapshots['ccavenue']['ready'] ?? false),
                'razorpay' => (bool) ($billingSnapshots['razorpay']['ready'] ?? false),
                'unlimit' => (bool) ($billingSnapshots['unlimit']['ready'] ?? false),
            ],
            'billingRequiredFieldLabelsByMethod' => collect($requiredBillingFieldsByMethod)
                ->map(fn (array $required) => $this->billingRequirements->labels($required))
                ->all(),
            'slug' => $slug,
        ]);
    }

    public function showCart(Request $request): mixed
    {
        return $this->legacy->showCart($request);
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
        if (Schema::hasColumn('orders', 'product_id') && $order->product_id) {
            return (int) $order->product_id === (int) $product->id;
        }

        if (Schema::hasColumn('orders', 'sku')) {
            return (string) $order->sku === (string) $product->sku;
        }

        // Fallback for older schemas: rely on order items.
        return $order->items()
            ->where('product_id', (int) $product->id)
            ->exists();
    }

    private function assertConsumerStorefrontProduct(Product $product): void
    {
        if (! $product->isListedOnConsumerStorefront()) {
            abort(404);
        }
    }
}

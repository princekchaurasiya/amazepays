<?php

declare(strict_types=1);

namespace App\Services\Checkout;

use App\Helpers\CheckoutHelper;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\GiftCardTheme;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\UserAddress;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CheckoutReadService
{
    public function findOrderForUser(?int $orderId, int $userId): ?Order
    {
        if (! $orderId) {
            return null;
        }

        return Order::query()
            ->whereKey($orderId)
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * @param  array<string, mixed>|null  $cachedBilling
     * @param  list<string>  $requiredFields
     * @return array{snapshot: array<string, string>, missing: array<int, string>, ready: bool}
     */
    public function buildBillingSnapshot(?Order $order, ?array $cachedBilling, ?User $user, array $requiredFields): array
    {
        $cached = $cachedBilling;
        if ($cached === null && $order?->id) {
            $cached = CheckoutHelper::getBillingData((int) $order->id);
        }

        $addr = $this->resolveDefaultBillingAddress($user);

        $snapshot = [
            'billing_name' => trim((string) ($cached['billing_name'] ?? $user?->name ?? '')),
            'billing_email' => trim((string) ($cached['billing_email'] ?? $user?->email ?? '')),
            'billing_tel' => trim((string) ($cached['billing_tel'] ?? $user?->mobile ?? '')),
            'billing_zip' => trim((string) ($cached['billing_zip'] ?? $addr?->postal_code ?? '')),
            'billing_address' => trim((string) ($cached['billing_address'] ?? $addr?->line1 ?? '')),
            'billing_address_two' => trim((string) ($cached['billing_address_two'] ?? $addr?->line2 ?? '')),
            'billing_city' => trim((string) ($cached['billing_city'] ?? $addr?->city ?? '')),
            'billing_state' => trim((string) ($cached['billing_state'] ?? $addr?->state ?? '')),
            'billing_country' => trim((string) ($cached['billing_country'] ?? $addr?->country ?? 'IN')),
            'billing_gst_number' => trim((string) ($cached['billing_gst_number'] ?? '')),
        ];

        $missing = [];
        foreach ($requiredFields as $field) {
            if (($snapshot[$field] ?? '') === '') {
                $missing[] = $field;
            }
        }

        return [
            'snapshot' => $snapshot,
            'missing' => $missing,
            'ready' => count($missing) === 0,
        ];
    }

    /**
     * Read optional checkout values from query params and accept only valid values.
     *
     * @return array<string, float|int|string>
     */
    public function extractCheckoutPrefillFromQuery(Request $request, Product $product): array
    {
        $out = [];

        $quantity = $request->query('quantity');
        if ($quantity !== null && $quantity !== '') {
            $q = (int) $quantity;
            if ($q >= 1 && $q <= 10) {
                $out['quantity'] = $q;
            }
        }

        $denomination = $request->query('denomination');
        if ($denomination === null || $denomination === '') {
            return $out;
        }

        $d = (float) $denomination;
        if (! is_finite($d) || $d <= 0) {
            return $out;
        }

        $priceData = (array) $product->price;
        $priceType = strtoupper((string) ($priceData['type'] ?? 'RANGE'));

        if ($priceType === 'SLAB') {
            $denominations = array_map(static function ($value): float {
                return (float) $value;
            }, (array) ($priceData['denominations'] ?? []));

            foreach ($denominations as $allowed) {
                if (abs($allowed - $d) < 0.00001) {
                    $out['denomination'] = $allowed;
                    break;
                }
            }

            return $out;
        }

        $min = isset($priceData['min']) ? (float) $priceData['min'] : (float) ($product->minPrice ?? 0);
        $max = isset($priceData['max']) ? (float) $priceData['max'] : (float) ($product->maxPrice ?? 0);

        if ($min > 0 && $max >= $min && $d >= $min && $d <= $max) {
            $out['denomination'] = $d;
        }

        $giftSendOption = (string) $request->query('gift_send_option', '');
        if (in_array($giftSendOption, ['buy_for_self', 'send_as_gift'], true)) {
            if ($this->isGiftOptionAllowedForProduct($product, $giftSendOption)) {
                $out['gift_send_option'] = $giftSendOption;
                $out['delivery_mode'] = 'both';
            }
        }

        if (($out['gift_send_option'] ?? null) === 'send_as_gift') {
            $giftThemeId = (int) $request->query('gift_theme_id', 0);
            if ($giftThemeId > 0 && GiftCardTheme::query()->active()->whereKey($giftThemeId)->exists()) {
                $out['gift_theme_id'] = $giftThemeId;
            }

            $messageTitle = trim((string) $request->query('gift_message_title', ''));
            if ($messageTitle !== '') {
                $out['gift_message_title'] = mb_substr($messageTitle, 0, 120);
            }

            $senderName = trim((string) $request->query('sender_first_name', ''));
            if ($senderName !== '') {
                $out['sender_first_name'] = mb_substr($senderName, 0, 120);
            }

            $receiverName = trim((string) $request->query('receiver_name', ''));
            if ($receiverName !== '') {
                $out['receiver_name'] = mb_substr($receiverName, 0, 255);
            }

            $receiverMessage = trim((string) $request->query('receiver_msg', ''));
            if ($receiverMessage !== '') {
                $out['receiver_msg'] = mb_substr($receiverMessage, 0, 500);
            }

            $receiverEmail = trim((string) $request->query('receiver_email', ''));
            if ($receiverEmail !== '' && filter_var($receiverEmail, FILTER_VALIDATE_EMAIL)) {
                $out['receiver_email'] = mb_substr($receiverEmail, 0, 255);
            }

            $receiverMobile = trim((string) $request->query('receiver_mobile', ''));
            if (preg_match('/^\d{10}$/', $receiverMobile)) {
                $out['receiver_mobile'] = $receiverMobile;
            }

            $giftDeliveryOption = trim((string) $request->query('gift_delivery_option', ''));
            if (in_array($giftDeliveryOption, ['send_now', 'send_later'], true)) {
                $out['gift_delivery_option'] = $giftDeliveryOption;
            }

            $giftDeliveryAt = trim((string) $request->query('gift_delivery_at', ''));
            if (($out['gift_delivery_option'] ?? null) === 'send_later' && $giftDeliveryAt !== '') {
                try {
                    $deliveryAt = Carbon::parse($giftDeliveryAt);
                    if ($deliveryAt->isFuture()) {
                        $out['gift_delivery_at'] = $deliveryAt->toDateTimeString();
                    }
                } catch (\Throwable) {
                    // Ignore invalid date strings from query params.
                }
            }
        }

        return $out;
    }

    /**
     * Restore checkout state from a persisted cart item owned by the current user.
     *
     * @return array<string, float|int|string>
     */
    public function extractCheckoutPrefillFromCartItem(Request $request, Product $product, int $userId): array
    {
        $cartItemId = (int) $request->query('cart_item', 0);
        if ($cartItemId <= 0) {
            return [];
        }

        $item = CartItem::query()
            ->whereKey($cartItemId)
            ->whereHas('cart', static fn ($query) => $query->where('user_id', $userId))
            ->first();

        if (! $item || (int) $item->product_id !== (int) $product->id) {
            return [];
        }

        return $this->prefillCheckoutFromCartLine($item);
    }

    /**
     * When there is no `?cart_item=`, hydrate checkout from the latest cart line for this product.
     *
     * @return array<string, float|int|string>
     */
    public function extractCheckoutPrefillFromLatestCartLine(Product $product, ?Cart $cart): array
    {
        if ($cart === null) {
            return [];
        }

        $item = $cart->items()
            ->where('product_id', (int) $product->id)
            ->latest('id')
            ->first();

        if ($item === null) {
            return [];
        }

        return $this->prefillCheckoutFromCartLine($item);
    }

    /**
     * @return array<string, float|int|string>
     */
    private function prefillCheckoutFromCartLine(CartItem $item): array
    {
        $out = [
            'denomination' => (float) $item->denomination,
            'quantity' => (int) $item->quantity,
            'gift_send_option' => (string) $item->gift_send_option,
            'delivery_mode' => 'both',
        ];

        if ($item->gift_send_option !== 'send_as_gift') {
            return $out;
        }

        if ($item->gift_theme_id && GiftCardTheme::query()->active()->whereKey($item->gift_theme_id)->exists()) {
            $out['gift_theme_id'] = (int) $item->gift_theme_id;
        }

        foreach ([
            'gift_message_title',
            'sender_first_name',
            'receiver_name',
            'receiver_msg',
            'receiver_email',
            'receiver_mobile',
            'gift_delivery_option',
        ] as $field) {
            $value = trim((string) ($item->{$field} ?? ''));
            if ($value !== '') {
                $out[$field] = $value;
            }
        }

        if (($out['gift_delivery_option'] ?? null) === 'send_later' && $item->gift_delivery_at) {
            $out['gift_delivery_at'] = $item->gift_delivery_at->toDateTimeString();
        }

        return $out;
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

    private function resolveDefaultBillingAddress(?User $user): ?UserAddress
    {
        if (! $user) {
            return null;
        }

        return $user->addresses()
            ->where('is_default_billing', true)
            ->orderByDesc('id')
            ->first()
            ?: $user->addresses()
                ->whereIn('type', ['billing', 'both'])
                ->orderByDesc('id')
                ->first();
    }
}

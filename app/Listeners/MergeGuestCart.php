<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\User;
use App\Services\Checkout\CartResolver;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\DB;

/**
 * After login, move guest cart lines (session_token) onto the customer's cart for the resolved tenant (Option A: keep duplicate lines).
 */
final class MergeGuestCart
{
    public function __construct(
        private readonly CartResolver $cartResolver,
    ) {}

    public function handle(Login $event): void
    {
        $request = request();

        $tokenRaw = session(CartResolver::GUEST_SESSION_KEY);
        $token = is_string($tokenRaw) && $tokenRaw !== '' ? $tokenRaw : null;

        if ($token === null) {
            return;
        }

        $tenantId = $this->cartResolver->tenantIdFromRequest($request);
        /** @var User $user */
        $user = $event->user;
        $userId = (int) $user->getAuthIdentifier();

        DB::transaction(function () use ($tenantId, $userId, $token): void {
            /** @var Cart|null $guestCart */
            $guestCart = Cart::query()
                ->where('tenant_id', $tenantId)
                ->where('session_token', $token)
                ->whereNull('user_id')
                ->first();

            if (! $guestCart) {
                return;
            }

            $lockedUserCart = Cart::query()->firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'user_id' => $userId,
                ],
                [
                    'status' => 'active',
                    'currency' => 'INR',
                    'subtotal_minor' => 0,
                    'discount_total_minor' => 0,
                    'tax_total_minor' => 0,
                    'grand_total_minor' => 0,
                    'session_token' => null,
                ]
            );

            if ($lockedUserCart->session_token !== null) {
                $lockedUserCart->forceFill(['session_token' => null])->save();
            }

            if ($guestCart->id !== $lockedUserCart->id) {
                CartItem::query()
                    ->where('cart_id', $guestCart->id)
                    ->update(['cart_id' => $lockedUserCart->id]);

                $guestCart->delete();
            }
        });

        session()->forget(CartResolver::GUEST_SESSION_KEY);
    }
}

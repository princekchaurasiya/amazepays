<?php

declare(strict_types=1);

namespace App\Services\Checkout;

use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Resolves tenant-scoped carts for guests (session_token) and logged-in buyers (user_id).
 */
final class CartResolver
{
    public const GUEST_SESSION_KEY = 'guest_cart_token';

    /**
     * Resolve storefront tenant ID from middleware bindings (never trust client input alone).
     */
    public function tenantIdFromRequest(Request $request): int
    {
        $tenant = $request->attributes->get('tenant');
        if ($tenant && method_exists($tenant, 'getKey')) {
            return (int) $tenant->getKey();
        }

        if (app()->bound('current_tenant_id')) {
            $id = app('current_tenant_id');
            if ($id !== null) {
                return (int) $id;
            }
        }

        if (Schema::hasTable('tenants')) {
            return (int) (DB::table('tenants')->orderBy('id')->value('id') ?? 1);
        }

        return 1;
    }

    /**
     * Active cart line for storefront cart mutations. Creates storage when `$create` is true.
     */
    public function resolve(Request $request, bool $create = true): ?Cart
    {
        $tenantId = $this->tenantIdFromRequest($request);

        if (Auth::check()) {
            return $this->resolveUserCart((int) Auth::id(), $tenantId, $create);
        }

        return $this->resolveGuestCart($request, $tenantId, $create);
    }

    /**
     * Read-only lookup for authenticated checkout prefill paths.
     */
    public function resolveForAuthenticatedPrefill(Request $request): ?Cart
    {
        if (! Auth::check()) {
            return null;
        }

        return $this->resolveUserCart((int) Auth::id(), $this->tenantIdFromRequest($request), false);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function newCartDefaults(array $attributes = []): array
    {
        return array_merge([
            'status' => 'active',
            'currency' => 'INR',
            'subtotal_minor' => 0,
            'discount_total_minor' => 0,
            'tax_total_minor' => 0,
            'grand_total_minor' => 0,
        ], $attributes);
    }

    private function resolveUserCart(int $userId, int $tenantId, bool $create): ?Cart
    {
        if (! $create) {
            return Cart::query()
                ->where('tenant_id', $tenantId)
                ->where('user_id', $userId)
                ->first();
        }

        $cart = Cart::query()->firstOrCreate(
            [
                'tenant_id' => $tenantId,
                'user_id' => $userId,
            ],
            $this->newCartDefaults([
                'user_id' => $userId,
                'session_token' => null,
            ])
        );

        if ($cart->session_token !== null) {
            $cart->forceFill(['session_token' => null])->save();
        }

        return $cart;
    }

    private function resolveGuestCart(Request $request, int $tenantId, bool $create): ?Cart
    {
        $existing = session(self::GUEST_SESSION_KEY);

        $token = is_string($existing) && strlen($existing) <= 64 && $existing !== ''
            ? $existing
            : null;

        if ($token === null && ! $create) {
            return null;
        }

        if ($token === null) {
            $token = Str::random(40);
            session([self::GUEST_SESSION_KEY => $token]);
        }

        $cart = Cart::query()
            ->where('tenant_id', $tenantId)
            ->where('session_token', $token)
            ->whereNull('user_id')
            ->first();

        if (! $cart && ! $create) {
            return null;
        }

        if ($cart !== null) {
            return $cart;
        }

        return Cart::query()->create($this->newCartDefaults([
            'tenant_id' => $tenantId,
            'user_id' => null,
            'session_token' => $token,
        ]));
    }
}

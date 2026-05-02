<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use App\Http\Middleware\VerifyCsrfToken;
use App\Listeners\MergeGuestCart;
use App\Models\Brand;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Checkout\CartResolver;
use App\Services\Checkout\CheckoutReadService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GuestCartFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        foreach (['super-admin', 'admin', 'finance', 'b2b-client', 'b2b-operator', 'b2c-user'] as $roleName) {
            Role::findOrCreate($roleName, 'web');
        }
        $this->withoutMiddleware([VerifyCsrfToken::class]);
    }

    private function tenantHeaders(Tenant $tenant): array
    {
        return ['X-Tenant-Slug' => $tenant->slug];
    }

    /**
     * @return array{0: Tenant, 1: Product}
     */
    private function tenantAndListedProduct(): array
    {
        $tenant = Tenant::factory()->create(['status' => 'active']);
        $brand = Brand::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Test Brand',
            'slug' => 'test-brand-'.uniqid(),
            'status' => 'active',
        ]);

        $slug = 'guest-flow-'.uniqid();
        $product = Product::query()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
            'sku' => 'SKU-'.uniqid(),
            'name' => 'Guest flow product',
            'slug' => $slug,
            'source_provider' => 'woohoo',
            'catalog_audience' => 'both',
        ]);

        DB::table('products')->where('id', '=', $product->getKey())->update([
            'published_at' => now(),
            'status' => 'active',
        ]);
        $product->refresh();

        DB::table('product_price_ranges')->insert([
            'product_id' => $product->id,
            'min_amount_minor' => 10 * 100,
            'max_amount_minor' => 5000 * 100,
            'step_amount_minor' => 100,
            'currency' => 'INR',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$tenant, $product];
    }

    public function test_guest_can_add_to_cart_and_session_token_is_stored(): void
    {
        [$tenant, $product] = $this->tenantAndListedProduct();

        $response = $this->withHeaders($this->tenantHeaders($tenant))
            ->from('/cart')
            ->post(route('storefront.cart.add', ['slug' => $product->slug]), [
                'denomination' => 100,
                'quantity' => 1,
                'gift_send_option' => 'buy_for_self',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas(CartResolver::GUEST_SESSION_KEY);
        $token = session(CartResolver::GUEST_SESSION_KEY);
        $this->assertIsString($token);
        $this->assertNotSame('', $token);

        $this->assertDatabaseHas('carts', [
            'tenant_id' => $tenant->id,
            'session_token' => $token,
            'user_id' => null,
        ]);

        $cart = Cart::query()->where('session_token', $token)->where('tenant_id', $tenant->id)->first();
        $this->assertNotNull($cart);
        $this->assertSame(1, $cart->items()->count());
    }

    public function test_merge_guest_cart_listener_moves_lines_to_user_cart(): void
    {
        [$tenant, $product] = $this->tenantAndListedProduct();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $token = 'merge-guest-test-token';
        $guestCart = Cart::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => null,
            'session_token' => $token,
            'status' => 'active',
            'currency' => 'INR',
        ]);

        CartItem::query()->create([
            'cart_id' => $guestCart->id,
            'product_id' => $product->id,
            'gift_send_option' => 'buy_for_self',
            'product_slug' => $product->slug,
            'sku' => (string) $product->sku,
            'product_name' => $product->name,
            'denomination' => 222,
            'quantity' => 1,
            'line_total' => 222,
            'gift_message_title' => null,
            'gift_delivery_option' => null,
            'gift_delivery_at' => null,
            'sender_first_name' => null,
            'receiver_name' => null,
            'receiver_email' => null,
            'receiver_mobile' => null,
            'receiver_msg' => null,
            'unit_amount_minor' => 22200,
            'currency' => 'INR',
            'gift_theme_id' => null,
        ]);

        app()->instance('current_tenant_id', $tenant->id);

        session([CartResolver::GUEST_SESSION_KEY => $token]);

        app(MergeGuestCart::class)->handle(new Login('web', $user, false));

        $guestCartFresh = Cart::query()->find($guestCart->id);
        $this->assertNull($guestCartFresh);

        $userCart = Cart::query()->where('tenant_id', $tenant->id)->where('user_id', $user->id)->firstOrFail();

        $this->assertSame(1, $userCart->items()->where('product_id', $product->id)->count());
        $this->assertNull(session(CartResolver::GUEST_SESSION_KEY));
    }

    public function test_latest_cart_line_prefills_checkout_prefill_payload(): void
    {
        [$tenant, $product] = $this->tenantAndListedProduct();
        $cart = Cart::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => null,
            'session_token' => 'fixture-token-guest-cart',
            'status' => 'active',
            'currency' => 'INR',
        ]);

        CartItem::query()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'gift_send_option' => 'buy_for_self',
            'product_slug' => $product->slug,
            'sku' => (string) $product->sku,
            'product_name' => $product->name,
            'denomination' => 250,
            'quantity' => 2,
            'line_total' => 500,
            'gift_message_title' => null,
            'gift_delivery_option' => null,
            'gift_delivery_at' => null,
            'sender_first_name' => null,
            'receiver_name' => null,
            'receiver_email' => null,
            'receiver_mobile' => null,
            'receiver_msg' => null,
            'unit_amount_minor' => 25000,
            'currency' => 'INR',
            'gift_theme_id' => null,
        ]);

        $payload = app(CheckoutReadService::class)->extractCheckoutPrefillFromLatestCartLine($product, $cart);

        $this->assertSame(250.0, (float) $payload['denomination']);
        $this->assertSame(2, (int) $payload['quantity']);
        $this->assertSame('buy_for_self', (string) $payload['gift_send_option']);
    }
}

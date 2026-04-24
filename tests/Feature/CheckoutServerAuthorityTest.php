<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CheckoutServerAuthorityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        foreach (['super-admin', 'admin', 'finance', 'b2b-client', 'b2b-operator', 'b2c-user'] as $roleName) {
            Role::findOrCreate($roleName, 'web');
        }
        $this->withoutMiddleware(HandleInertiaRequests::class);
    }

    /**
     * @return array<string, string>
     */
    private function inertiaHeaders(): array
    {
        $version = (new HandleInertiaRequests)->version(Request::create('/'));

        return array_filter([
            'X-Inertia' => 'true',
            'X-Inertia-Version' => $version,
        ]);
    }

    private function createTransactingUser(): User
    {
        return User::factory()->create([
            'is_blocked' => false,
            'can_transact' => true,
        ]);
    }

    public function test_checkout_payload_exposes_only_server_authoritative_order_fields(): void
    {
        $user = $this->createTransactingUser();
        $product = Product::query()->create([
            'sku' => 'CHK-SKU-001',
            'name' => 'Checkout Product',
            'url' => 'checkout-product',
            'source_provider' => 'woohoo',
            'show_product' => true,
            'catalog_audience' => 'both',
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'amount_payable_after_discount' => 2500,
            'grand_payable_amount' => 2600,
            'denomination' => 9999,
            'quantity' => 99,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['checkout_order_id' => $order->id])
            ->get('/checkout/checkout-product', $this->inertiaHeaders());

        $response->assertOk();
        $propsOrder = $response->json('props.order');

        $this->assertSame($order->id, $propsOrder['id']);
        $this->assertSame(2500.0, (float) $propsOrder['amount_payable_after_discount']);
        $this->assertSame(2600.0, (float) $propsOrder['grand_payable_amount']);
        $this->assertArrayNotHasKey('denomination', $propsOrder);
        $this->assertArrayNotHasKey('quantity', $propsOrder);
    }

    public function test_upi_store_rejects_unexpected_request_fields(): void
    {
        $user = $this->createTransactingUser();

        $response = $this->actingAs($user)
            ->from('/checkout/demo')
            ->post('/payment/upi', [
                'order_id' => 1,
                'forged_total' => 1,
            ]);

        $response->assertRedirect('/checkout/demo');
        $response->assertSessionHasErrors('unexpected_fields');
    }

    public function test_checkout_view_ignores_legacy_checkout_data_session_values(): void
    {
        $user = $this->createTransactingUser();
        $product = Product::query()->create([
            'sku' => 'CHK-SKU-002',
            'name' => 'Checkout Product 2',
            'url' => 'checkout-product-2',
            'source_provider' => 'woohoo',
            'show_product' => true,
            'catalog_audience' => 'both',
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'denomination' => 100,
            'quantity' => 2,
            'gift_send_option' => 'buy_for_self',
            'amount_payable_after_discount' => 200,
        ]);

        $response = $this->actingAs($user)
            ->withSession([
                'checkout_order_id' => $order->id,
                'checkout_data' => ['denomination' => 1, 'quantity' => 99],
            ])
            ->get('/checkout/checkout-product-2', $this->inertiaHeaders());

        $response->assertOk();
        $checkoutData = $response->json('props.checkoutData');
        $this->assertSame(100, (int) ($checkoutData['denomination'] ?? 0));
        $this->assertSame(2, (int) ($checkoutData['quantity'] ?? 0));
    }

    public function test_update_session_data_rejects_monetary_fields(): void
    {
        $user = $this->createTransactingUser();
        $order = Order::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->withSession(['checkout_order_id' => $order->id])
            ->postJson('/update-session-data', [
                'billing_name' => 'Demo User',
                'billing_email' => 'demo@example.com',
                'denomination' => 5000,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('unexpected_fields');
    }

    public function test_checkout_ignores_stale_order_id_from_another_product_slug(): void
    {
        $user = $this->createTransactingUser();
        $productA = Product::query()->create([
            'sku' => 'CHK-SKU-003A',
            'name' => 'Checkout Product A',
            'url' => 'checkout-product-3-a',
            'source_provider' => 'woohoo',
            'show_product' => true,
            'catalog_audience' => 'both',
        ]);
        $productB = Product::query()->create([
            'sku' => 'CHK-SKU-003B',
            'name' => 'Checkout Product B',
            'url' => 'checkout-product-3-b',
            'source_provider' => 'woohoo',
            'show_product' => true,
            'catalog_audience' => 'both',
        ]);

        $orderForA = Order::factory()->create([
            'user_id' => $user->id,
            'product_id' => $productA->id,
            'amount_payable_after_discount' => 1200,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['checkout_order_id' => $orderForA->id])
            ->get('/checkout/checkout-product-3-b', $this->inertiaHeaders());

        $response->assertOk();
        $this->assertNull($response->json('props.order.id'));
    }

    public function test_checkout_query_is_normalized_to_clean_url_and_recovers_draft(): void
    {
        $user = $this->createTransactingUser();
        $product = Product::query()->create([
            'sku' => 'CHK-SKU-004',
            'name' => 'Checkout Product 4',
            'url' => 'checkout-product-4',
            'source_provider' => 'woohoo',
            'show_product' => true,
            'catalog_audience' => 'both',
            'price' => json_encode([
                'type' => 'RANGE',
                'min' => 1,
                'max' => 1000,
            ]),
            'discount_percentage' => 0,
        ]);

        $response = $this->actingAs($user)
            ->get('/checkout/checkout-product-4?denomination=100&quantity=2&gift_send_option=buy_for_self', $this->inertiaHeaders());

        $response->assertRedirect('/checkout/checkout-product-4');

        $draftOrder = Order::query()
            ->where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($draftOrder);

        $follow = $this->actingAs($user)
            ->withSession(['checkout_order_id' => $draftOrder->id])
            ->get('/checkout/checkout-product-4', $this->inertiaHeaders());

        $follow->assertOk();
        $orderId = $follow->json('props.order.id');
        $this->assertNotNull($orderId);
        $this->assertSame(200.0, (float) $follow->json('props.order.amount_payable_after_discount'));
    }

    public function test_checkout_post_uses_server_upsert_and_redirects_to_clean_url(): void
    {
        $user = $this->createTransactingUser();
        Product::query()->create([
            'sku' => 'CHK-SKU-005',
            'name' => 'Checkout Product 5',
            'url' => 'checkout-product-5',
            'source_provider' => 'woohoo',
            'show_product' => true,
            'catalog_audience' => 'both',
            'price' => json_encode([
                'type' => 'RANGE',
                'min' => 1,
                'max' => 1000,
            ]),
            'discount_percentage' => 0,
        ]);

        $response = $this->actingAs($user)->post('/checkout/checkout-product-5', [
            'denomination' => 6,
            'quantity' => 6,
            'gift_send_option' => 'buy_for_self',
        ]);

        $response->assertRedirect('/checkout/checkout-product-5');
    }
}

<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Order> */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'order_number' => 'ORD-'.$this->faker->unique()->numerify('########'),
            'channel' => 'storefront',
            'status' => 'initiated',
            'subtotal_minor' => 10_000,
            'discount_total_minor' => 0,
            'tax_total_minor' => 0,
            'grand_total_minor' => 10_000,
            'currency' => 'INR',
            'placed_at' => now(),
        ];
    }
}

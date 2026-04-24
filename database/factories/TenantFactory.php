<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Tenant> */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $name = $this->faker->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.$this->faker->unique()->numerify('###'),
            'display_name' => $name,
            'type' => 'b2c_brand',
            'status' => 'active',
            'default_locale' => 'en',
            'default_currency' => 'INR',
            'default_timezone' => 'Asia/Kolkata',
            'settings' => null,
        ];
    }
}

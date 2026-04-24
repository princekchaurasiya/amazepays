<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Immutable shipping snapshot captured at order placement. Only present for physical-delivery orders.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_shipping_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('user_address_id')->nullable()->constrained('user_addresses')->cascadeOnUpdate()->nullOnDelete();
            $table->string('full_name');
            $table->string('phone', 32)->nullable();
            $table->string('line1');
            $table->string('line2')->nullable();
            $table->string('city', 128);
            $table->string('state', 128);
            $table->string('postal_code', 32);
            $table->string('country', 2)->default('IN');
            $table->string('courier', 64)->nullable();
            $table->string('tracking_number', 128)->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_shipping_snapshots');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reusable billing/shipping addresses for a user. Order-time snapshots live in order_billing_snapshots /
 * order_shipping_snapshots so historical orders stay immutable when addresses are edited.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->enum('type', ['billing', 'shipping', 'both'])->default('both');
            $table->string('label')->nullable();
            $table->string('full_name');
            $table->string('phone', 32)->nullable();
            $table->string('line1');
            $table->string('line2')->nullable();
            $table->string('landmark')->nullable();
            $table->string('city', 128);
            $table->string('state', 128);
            $table->string('postal_code', 32);
            $table->string('country', 2)->default('IN');
            $table->boolean('is_default_billing')->default(false);
            $table->boolean('is_default_shipping')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'type']);
            $table->index(['country', 'postal_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_addresses');
    }
};

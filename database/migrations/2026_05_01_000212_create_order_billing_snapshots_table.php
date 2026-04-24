<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Immutable billing snapshot captured at order placement. Future user_addresses edits do not affect history.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_billing_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('user_address_id')->nullable()->constrained('user_addresses')->cascadeOnUpdate()->nullOnDelete();
            $table->string('full_name');
            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('line1');
            $table->string('line2')->nullable();
            $table->string('city', 128);
            $table->string('state', 128);
            $table->string('postal_code', 32);
            $table->string('country', 2)->default('IN');
            $table->string('gst_number', 32)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_billing_snapshots');
    }
};

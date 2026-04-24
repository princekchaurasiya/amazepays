<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Device + client fingerprint captured at checkout for fraud analytics. 1:1 with orders.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_device_context', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('trusted_device_id')->nullable()->constrained('trusted_devices')->cascadeOnUpdate()->nullOnDelete();
            $table->string('ip_address', 64)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('device_fingerprint', 128)->nullable();
            $table->string('country', 2)->nullable();
            $table->string('city', 128)->nullable();
            $table->string('isp')->nullable();
            $table->enum('channel', ['web', 'mobile_app', 'ios', 'android', 'api'])->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_device_context');
    }
};

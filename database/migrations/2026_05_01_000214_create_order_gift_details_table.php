<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-line gift recipient details (immutable snapshot copied from cart_gift_details at checkout).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_gift_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->unique()->constrained('order_items')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('recipient_name')->nullable();
            $table->string('recipient_email')->nullable();
            $table->string('recipient_mobile', 32)->nullable();
            $table->text('gift_message')->nullable();
            $table->string('sender_name')->nullable();
            $table->timestamp('scheduled_delivery_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->enum('delivery_status', ['pending', 'scheduled', 'sent', 'failed'])->default('pending');
            $table->string('last_delivery_error', 1024)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_gift_details');
    }
};

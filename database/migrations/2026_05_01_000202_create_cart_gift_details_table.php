<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gift-recipient info attached to a cart item (if the buyer is sending the voucher to someone else).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_gift_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_item_id')->unique()->constrained('cart_items')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('recipient_name')->nullable();
            $table->string('recipient_email')->nullable();
            $table->string('recipient_mobile', 32)->nullable();
            $table->text('gift_message')->nullable();
            $table->timestamp('scheduled_delivery_at')->nullable();
            $table->string('sender_name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_gift_details');
    }
};

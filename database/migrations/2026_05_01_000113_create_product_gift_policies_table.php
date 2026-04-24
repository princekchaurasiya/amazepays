<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-product gift delivery policy (send-to-recipient allowed, scheduled delivery, message support).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_gift_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained('products')->cascadeOnUpdate()->cascadeOnDelete();
            $table->boolean('allow_send_to_recipient')->default(true);
            $table->boolean('allow_scheduled_delivery')->default(false);
            $table->boolean('allow_gift_message')->default(true);
            $table->unsignedSmallInteger('gift_message_max_chars')->default(500);
            $table->unsignedSmallInteger('validity_days')->default(365);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_gift_policies');
    }
};

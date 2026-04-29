<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_offer_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_offer_id')->constrained('bank_offers')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->bigInteger('discount_amount_minor');
            $table->char('currency', 3)->default('INR');
            $table->timestamp('applied_at');
            $table->timestamps();

            $table->index(['bank_offer_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_offer_usages');
    }
};

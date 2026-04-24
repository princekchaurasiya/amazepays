<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Loyalty redemption against an order (header). Line detail lives in loyalty_point_transactions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('loyalty_accounts')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('point_transaction_id')->nullable()->constrained('loyalty_point_transactions')->cascadeOnUpdate()->nullOnDelete();
            $table->bigInteger('points_redeemed');
            $table->bigInteger('discount_amount_minor');
            $table->char('currency', 3)->default('INR');
            $table->enum('status', ['pending', 'applied', 'reversed'])->default('applied')->index();
            $table->timestamp('applied_at');
            $table->timestamps();

            $table->index(['account_id', 'order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_redemptions');
    }
};

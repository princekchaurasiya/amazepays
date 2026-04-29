<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offer_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained('offers')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->bigInteger('discount_amount_minor');
            $table->char('currency', 3)->default('INR');
            $table->timestamp('redeemed_at');
            $table->timestamps();

            $table->index(['offer_id', 'user_id']);
            $table->index(['offer_id', 'redeemed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_usages');
    }
};

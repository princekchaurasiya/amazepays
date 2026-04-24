<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Customer-initiated wallet top-up requests. Linked to a payment. On success a credit wallet_transaction is written.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_load_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('wallets')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->cascadeOnUpdate()->nullOnDelete();
            $table->enum('status', ['initiated', 'processing', 'succeeded', 'failed', 'cancelled'])->default('initiated');
            $table->bigInteger('amount_minor');
            $table->char('currency', 3)->default('INR');
            $table->string('failure_reason', 512)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['wallet_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_load_requests');
    }
};

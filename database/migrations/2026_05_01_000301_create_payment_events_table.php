<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Immutable audit of raw webhook / callback / status-query payloads per payment.
 * Append-only. Signature + received_from_ip captured for forensic review.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnUpdate()->cascadeOnDelete();
            $table->enum('source', ['initiate', 'redirect_return', 'webhook', 'status_query', 'reconciliation', 'manual'])->index();
            $table->string('event_type', 64);
            $table->string('gateway_status', 64)->nullable();
            $table->longText('raw_payload');
            $table->string('signature_header', 2048)->nullable();
            $table->boolean('signature_verified')->default(false);
            $table->string('received_from_ip', 64)->nullable();
            $table->string('dispatch_id', 128)->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['payment_id', 'occurred_at']);
            $table->index('dispatch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_events');
    }
};

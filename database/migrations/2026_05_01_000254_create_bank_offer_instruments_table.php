<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_offer_instruments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_offer_id')->constrained('bank_offers')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('payment_instrument_id')->constrained('payment_instruments')->cascadeOnUpdate()->cascadeOnDelete();
            $table->timestamps();

            // Explicitly name the index to stay within MySQL identifier limits.
            $table->unique(['bank_offer_id', 'payment_instrument_id'], 'boi_offer_instrument_uniq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_offer_instruments');
    }
};

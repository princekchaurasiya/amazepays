<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_offer_emi_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_offer_id')->constrained('bank_offers')->cascadeOnUpdate()->cascadeOnDelete();
            $table->unsignedTinyInteger('tenure_months');
            $table->decimal('interest_rate_percent', 6, 3)->default(0);
            $table->bigInteger('processing_fee_minor')->default(0);
            $table->bigInteger('min_transaction_amount_minor')->default(0);
            $table->boolean('is_no_cost')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['bank_offer_id', 'tenure_months']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_offer_emi_options');
    }
};

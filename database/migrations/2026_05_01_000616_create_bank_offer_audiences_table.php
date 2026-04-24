<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_offer_audiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_offer_id')->constrained('bank_offers')->cascadeOnUpdate()->cascadeOnDelete();
            $table->enum('audience_type', ['b2c_public', 'b2b_partner', 'corporate', 'loyalty_tier', 'tenant'])->index();
            $table->unsignedBigInteger('audience_ref_id')->nullable();
            $table->timestamps();

            $table->unique(['bank_offer_id', 'audience_type', 'audience_ref_id'], 'bank_offer_audiences_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_offer_audiences');
    }
};

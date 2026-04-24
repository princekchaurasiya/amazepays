<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offer_audiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained('offers')->cascadeOnUpdate()->cascadeOnDelete();
            $table->enum('audience_type', ['b2c_public', 'b2b_partner', 'corporate', 'loyalty_tier', 'tenant', 'user'])->index();
            $table->unsignedBigInteger('audience_ref_id')->nullable();
            $table->timestamps();

            $table->unique(['offer_id', 'audience_type', 'audience_ref_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_audiences');
    }
};

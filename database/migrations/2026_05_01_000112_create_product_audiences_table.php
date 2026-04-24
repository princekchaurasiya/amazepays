<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which audiences see a product (B2C public, B2B partners, corporate, specific loyalty tiers).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_audiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnUpdate()->cascadeOnDelete();
            $table->enum('audience_type', ['b2c_public', 'b2b_partner', 'corporate', 'loyalty_tier', 'tenant'])->index();
            $table->unsignedBigInteger('audience_ref_id')->nullable();
            $table->timestamps();

            // Explicitly name the index to stay within MySQL identifier limits.
            $table->unique(['product_id', 'audience_type', 'audience_ref_id'], 'prod_aud_prod_type_ref_uniq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_audiences');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_discount_audiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_discount_id')->constrained('product_discounts')->cascadeOnUpdate()->cascadeOnDelete();
            $table->enum('audience_type', ['b2c_public', 'b2b_partner', 'corporate', 'loyalty_tier', 'tenant'])->index();
            $table->unsignedBigInteger('audience_ref_id')->nullable();
            $table->timestamps();

            $table->unique(['product_discount_id', 'audience_type', 'audience_ref_id'], 'product_discount_audiences_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_discount_audiences');
    }
};

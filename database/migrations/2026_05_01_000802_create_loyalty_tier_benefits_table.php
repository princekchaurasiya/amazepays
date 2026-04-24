<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Benefits attached to a loyalty tier (e.g. 5% bonus points, free shipping, early access).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_tier_benefits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tier_id')->constrained('loyalty_tiers')->cascadeOnUpdate()->cascadeOnDelete();
            $table->enum('benefit_type', ['point_multiplier', 'percentage_discount', 'fixed_discount', 'free_shipping', 'early_access', 'priority_support'])->index();
            $table->decimal('multiplier', 6, 3)->nullable();
            $table->decimal('discount_percent', 6, 3)->nullable();
            $table->bigInteger('discount_amount_minor')->nullable();
            $table->string('label');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_tier_benefits');
    }
};

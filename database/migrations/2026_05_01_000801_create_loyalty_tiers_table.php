<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tiers within a loyalty program (Silver, Gold, Platinum). Thresholds + duration configurable per tier.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('loyalty_programs')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug', 64);
            $table->unsignedInteger('display_order')->default(0);
            $table->bigInteger('qualifying_spend_minor')->default(0);
            $table->unsignedInteger('qualifying_orders')->default(0);
            $table->unsignedInteger('tier_duration_days')->default(365);
            $table->string('badge_image_url', 512)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['program_id', 'slug']);
            $table->index(['program_id', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_tiers');
    }
};

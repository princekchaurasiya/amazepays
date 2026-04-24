<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-user loyalty account. Balance is computed from loyalty_point_transactions; columns here are cached.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('loyalty_programs')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('current_tier_id')->nullable()->constrained('loyalty_tiers')->cascadeOnUpdate()->nullOnDelete();
            $table->bigInteger('points_balance')->default(0);
            $table->bigInteger('points_lifetime_earned')->default(0);
            $table->bigInteger('points_lifetime_redeemed')->default(0);
            $table->bigInteger('points_pending')->default(0);
            $table->timestamp('tier_expires_at')->nullable();
            $table->timestamps();

            $table->unique(['program_id', 'user_id']);
            $table->index('current_tier_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_accounts');
    }
};

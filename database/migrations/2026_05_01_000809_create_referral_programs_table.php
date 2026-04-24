<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-tenant referral program config (referrer reward + referee reward).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('loyalty_program_id')->nullable()->constrained('loyalty_programs')->cascadeOnUpdate()->nullOnDelete();
            $table->string('name');
            $table->string('slug', 64);
            $table->bigInteger('referrer_points_bonus')->default(0);
            $table->bigInteger('referee_points_bonus')->default(0);
            $table->bigInteger('referrer_cashback_minor')->default(0);
            $table->bigInteger('referee_cashback_minor')->default(0);
            $table->bigInteger('min_referee_first_order_minor')->default(0);
            $table->char('currency', 3)->default('INR');
            $table->enum('status', ['draft', 'active', 'paused', 'ended'])->default('draft');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'slug']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_programs');
    }
};

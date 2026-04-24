<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Top-level loyalty program (e.g. "AmazePays Rewards"). One program per tenant (or shared platform program).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug', 64);
            $table->string('point_currency_label', 32)->default('Points');
            $table->decimal('point_to_currency_rate', 12, 6)->default(1);
            $table->char('currency', 3)->default('INR');
            $table->enum('status', ['draft', 'active', 'paused', 'ended'])->default('draft');
            $table->unsignedInteger('points_expiry_days')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'slug']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_programs');
    }
};

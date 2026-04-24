<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rewards catalog (physical/digital items redeemable by points). Separate from products because the
 * price is in points, not currency.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_rewards_catalog', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('loyalty_programs')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('linked_product_id')->nullable()->constrained('products')->cascadeOnUpdate()->nullOnDelete();
            $table->string('name');
            $table->string('slug', 191);
            $table->text('description')->nullable();
            $table->string('image_url', 512)->nullable();
            $table->bigInteger('points_cost');
            $table->unsignedInteger('stock_remaining')->nullable();
            $table->enum('status', ['draft', 'active', 'paused', 'sold_out', 'ended'])->default('draft');
            $table->timestamp('active_from')->nullable();
            $table->timestamp('active_until')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['program_id', 'slug']);
            $table->index(['program_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_rewards_catalog');
    }
};

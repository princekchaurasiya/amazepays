<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-product sync state from upstream provider (Woohoo / Vouchagram / VD). 1:1 with products.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_source_sync', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained('products')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('provider', 32);
            $table->string('external_product_id');
            $table->string('external_sku')->nullable();
            $table->enum('sync_status', ['healthy', 'stale', 'failing', 'deprecated'])->default('healthy');
            $table->string('last_sync_error', 1024)->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->json('last_raw_payload')->nullable();
            $table->timestamps();

            $table->index(['provider', 'sync_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_source_sync');
    }
};

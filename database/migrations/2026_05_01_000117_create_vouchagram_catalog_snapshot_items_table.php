<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-brand row of a Vouchagram snapshot. Uniqueness enforced per (snapshot, brand_code, api_mode).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchagram_catalog_snapshot_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('snapshot_id')->constrained('vouchagram_catalog_snapshots')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->cascadeOnUpdate()->nullOnDelete();
            $table->string('brand_code');
            $table->enum('api_mode', ['send', 'pull'])->default('send');
            $table->string('brand_name');
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            // Explicitly name the index to stay within MySQL identifier limits.
            $table->unique(['snapshot_id', 'brand_code', 'api_mode'], 'vcs_item_snap_code_mode_uniq');
            $table->index(['api_mode', 'brand_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchagram_catalog_snapshot_items');
    }
};

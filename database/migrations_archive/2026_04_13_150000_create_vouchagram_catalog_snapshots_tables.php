<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchagram_catalog_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('mode', 10);
            $table->string('brand_product_code_filter', 100)->nullable();
            $table->unsignedInteger('item_count');
            $table->timestamp('fetched_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['mode', 'fetched_at'], 'vg_cat_snap_mode_fetched_idx');
        });

        Schema::create('vouchagram_catalog_snapshot_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('snapshot_id')->constrained('vouchagram_catalog_snapshots')->cascadeOnDelete();
            $table->string('brand_product_code', 100);
            $table->json('payload');
            $table->timestamps();

            $table->index('snapshot_id', 'vg_cat_snap_items_snap_idx');
            $table->index(['snapshot_id', 'brand_product_code'], 'vg_cat_snap_items_snap_code_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchagram_catalog_snapshot_items');
        Schema::dropIfExists('vouchagram_catalog_snapshots');
    }
};

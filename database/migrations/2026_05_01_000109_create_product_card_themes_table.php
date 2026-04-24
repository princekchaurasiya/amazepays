<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-SKU card visuals (optional override; brand_card_themes is the default).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_card_themes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained('products')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('primary_color', 32)->nullable();
            $table->string('secondary_color', 32)->nullable();
            $table->string('card_front_image_url', 512)->nullable();
            $table->string('card_back_image_url', 512)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_card_themes');
    }
};

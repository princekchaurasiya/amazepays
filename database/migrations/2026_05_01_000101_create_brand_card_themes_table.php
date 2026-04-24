<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Card theme (colors / background images) per brand. 1:1 with brands.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brand_card_themes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->unique()->constrained('brands')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('primary_color', 32)->nullable();
            $table->string('secondary_color', 32)->nullable();
            $table->string('text_color', 32)->nullable();
            $table->string('card_front_image_url', 512)->nullable();
            $table->string('card_back_image_url', 512)->nullable();
            $table->string('gift_mail_image_url', 512)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_card_themes');
    }
};

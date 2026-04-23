<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('brand_card_themes')) {
            return;
        }

        Schema::create('brand_card_themes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->nullable()->index();
            $table->unsignedBigInteger('brand_id')->nullable()->index();
            $table->string('brand_name')->nullable()->index();
            $table->string('logo_url')->nullable();
            $table->string('bg_color', 16)->nullable();
            $table->string('text_color', 16)->nullable();
            $table->string('accent_color', 16)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('priority')->default(0)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_card_themes');
    }
};

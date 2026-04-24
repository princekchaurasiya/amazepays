<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Long-form copy for a product. Separated so product list queries never load large text blobs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_descriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained('products')->cascadeOnUpdate()->cascadeOnDelete();
            $table->text('short_description')->nullable();
            $table->longText('long_description')->nullable();
            $table->longText('terms_and_conditions')->nullable();
            $table->longText('how_to_use')->nullable();
            $table->longText('about_brand')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_descriptions');
    }
};

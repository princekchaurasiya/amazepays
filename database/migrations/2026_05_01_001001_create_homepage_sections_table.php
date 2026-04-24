<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin-editable storefront/B2B home page sections. Section_type drives the renderer component.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homepage_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnUpdate()->cascadeOnDelete();
            $table->enum('surface', ['storefront_home', 'b2b_home', 'category_landing'])->default('storefront_home')->index();
            $table->enum('section_type', [
                'hero_carousel', 'featured_brands', 'top_categories', 'new_arrivals',
                'bestsellers', 'seasonal', 'usp_band', 'testimonials', 'faq', 'custom_html',
            ])->index();
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->json('payload')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'surface', 'is_active', 'display_order'], 'homepage_sections_layout_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homepage_sections');
    }
};

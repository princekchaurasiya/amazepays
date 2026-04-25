<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_section_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('content_section_id')->constrained('content_sections')->cascadeOnUpdate()->cascadeOnDelete();

            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_enabled')->default(true)->index();
            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->unsignedInteger('priority')->default(0);

            // Generic item content.
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();

            // Responsive media for the item.
            $table->foreignId('web_media_asset_id')->nullable()->constrained('media_assets')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('mobile_media_asset_id')->nullable()->constrained('media_assets')->nullOnDelete()->cascadeOnUpdate();

            // CTA fields for web + mobile.
            $table->string('cta_text')->nullable();
            $table->string('cta_type', 32)->nullable();   // url, deeplink, product, category, brand, custom
            $table->string('cta_value', 512)->nullable(); // target value for cta_type
            $table->string('deeplink', 512)->nullable();
            $table->string('redirect_url', 512)->nullable();

            // Optional structured links.
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete()->cascadeOnUpdate();

            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(
                ['tenant_id', 'content_section_id', 'is_enabled', 'sort_order'],
                'content_section_items_section_enabled_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_section_items');
    }
};


<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_banners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->nullable()->constrained('promotion_campaigns')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnUpdate()->cascadeOnDelete();
            $table->enum('surface', ['storefront_home', 'category', 'product_page', 'cart', 'checkout', 'b2b_home'])->index();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('image_url', 512);
            $table->string('cta_label')->nullable();
            $table->string('cta_url', 512)->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('active_from')->nullable();
            $table->timestamp('active_until')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'surface', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_banners');
    }
};

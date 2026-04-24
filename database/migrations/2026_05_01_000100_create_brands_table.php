<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Merchant brand registry. Tenant-scoped. Source provider identifies which external catalog owns the brand.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug', 191);
            $table->string('source_provider', 32)->nullable();
            $table->string('source_brand_id')->nullable();
            $table->string('logo_url', 512)->nullable();
            $table->string('hero_image_url', 512)->nullable();
            $table->enum('status', ['active', 'draft', 'archived', 'sunset'])->default('active');
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'slug']);
            $table->unique(['source_provider', 'source_brand_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'is_featured']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};

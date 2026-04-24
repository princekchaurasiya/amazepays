<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Polymorphic SEO meta records - one row per (route_key or model + locale). Replaces scattered
 * per-table seo_title/seo_description columns so the SEO domain is normalized + auditable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_metas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnUpdate()->cascadeOnDelete();
            $table->nullableMorphs('subject');
            $table->string('route_key', 191)->nullable();
            $table->string('locale', 10)->default('en');
            $table->string('meta_title', 191)->nullable();
            $table->string('meta_description', 512)->nullable();
            $table->string('meta_keywords', 512)->nullable();
            $table->string('canonical_url', 512)->nullable();
            $table->string('og_title', 191)->nullable();
            $table->string('og_description', 512)->nullable();
            $table->string('og_image_url', 512)->nullable();
            $table->string('twitter_card', 32)->nullable();
            $table->boolean('is_indexable')->default(true);
            $table->boolean('is_follow')->default(true);
            $table->json('structured_data')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'route_key', 'locale'], 'seo_metas_route_unique');
            $table->index(['tenant_id', 'is_indexable']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_metas');
    }
};

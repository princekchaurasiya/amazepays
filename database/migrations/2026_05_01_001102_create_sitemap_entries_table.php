<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Denormalized rows consumed by the sitemap generator job. Not the source of truth for content -
 * refreshed from products/categories/brands/static_pages.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sitemap_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnUpdate()->cascadeOnDelete();
            $table->nullableMorphs('subject');
            $table->string('loc', 512);
            $table->string('locale', 10)->default('en');
            $table->enum('change_frequency', ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'])->default('weekly');
            $table->decimal('priority', 3, 2)->default(0.50);
            $table->timestamp('last_modified_at')->nullable();
            $table->boolean('is_indexable')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'loc', 'locale']);
            $table->index(['tenant_id', 'is_indexable']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sitemap_entries');
    }
};

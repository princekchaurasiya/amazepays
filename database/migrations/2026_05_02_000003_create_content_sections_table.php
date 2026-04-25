<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnUpdate()->cascadeOnDelete();

            // Where this section appears.
            $table->string('surface', 64)->default('storefront_home')->index();

            // Section configuration.
            $table->string('slug', 191);
            $table->string('type', 64)->index(); // e.g. banner_single, carousel, grid_2
            $table->enum('status', ['active', 'draft', 'archived'])->default('active')->index();
            $table->boolean('is_enabled')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);

            // Targeting.
            $table->enum('platform', ['web', 'mobile', 'both'])->default('both')->index();
            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->unsignedInteger('priority')->default(0);

            // Display.
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->string('background_color', 32)->nullable();
            $table->string('text_color', 32)->nullable();
            $table->json('metadata')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'surface', 'slug']);
            $table->index(
                ['tenant_id', 'surface', 'is_enabled', 'status', 'sort_order'],
                'content_sections_tenant_surface_enabled_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_sections');
    }
};


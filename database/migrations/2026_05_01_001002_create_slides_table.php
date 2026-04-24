<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hero-carousel slide content, optionally scoped to a homepage_section.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('homepage_section_id')->nullable()->constrained('homepage_sections')->cascadeOnUpdate()->nullOnDelete();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('image_url', 512);
            $table->string('mobile_image_url', 512)->nullable();
            $table->string('cta_label')->nullable();
            $table->string('cta_url', 512)->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('active_from')->nullable();
            $table->timestamp('active_until')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'is_active', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slides');
    }
};

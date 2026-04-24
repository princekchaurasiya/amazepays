<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Seasonal gift mail themes (Birthday, Diwali, etc.). Not specific to a brand or product.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gift_themes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug', 191);
            $table->string('preview_image_url', 512)->nullable();
            $table->string('email_template_path', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->date('active_from')->nullable();
            $table->date('active_until')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'slug']);
            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gift_themes');
    }
};

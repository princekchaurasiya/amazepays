<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Self-referential category hierarchy. Parent FK allows nested browsing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug', 191);
            $table->string('description')->nullable();
            $table->string('icon_url', 512)->nullable();
            $table->string('banner_url', 512)->nullable();
            $table->unsignedSmallInteger('depth')->default(0);
            $table->unsignedInteger('display_order')->default(0);
            $table->enum('status', ['active', 'archived'])->default('active');
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'slug']);
            $table->index(['tenant_id', 'parent_id', 'display_order']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};

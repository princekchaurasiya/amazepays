<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_banners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnUpdate()->cascadeOnDelete();

            $table->foreignId('web_media_asset_id')->nullable()->constrained('media_assets')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('mobile_media_asset_id')->nullable()->constrained('media_assets')->nullOnDelete()->cascadeOnUpdate();

            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->unsignedInteger('priority')->default(0);
            $table->boolean('is_enabled')->default(true)->index();

            $table->string('cta_text')->nullable();
            $table->string('cta_type', 32)->nullable();
            $table->string('cta_value', 512)->nullable();
            $table->string('deeplink', 512)->nullable();
            $table->string('redirect_url', 512)->nullable();

            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'category_id', 'is_enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_banners');
    }
};


<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homepage_layout_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnUpdate()->cascadeOnDelete();

            $table->unsignedInteger('version')->default(1);
            $table->timestamp('published_at')->nullable();

            // Snapshot is a fully ordered section list with any publish-time overrides.
            $table->json('layout_snapshot');
            $table->string('experiment_key', 64)->nullable(); // future A/B support

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamps();

            $table->unique(['tenant_id', 'version']);
            $table->index(['tenant_id', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homepage_layout_versions');
    }
};


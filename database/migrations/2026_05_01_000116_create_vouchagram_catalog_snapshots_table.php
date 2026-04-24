<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Periodic Vouchagram catalog snapshot header (per sync run).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchagram_catalog_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnUpdate()->cascadeOnDelete();
            $table->enum('api_mode', ['send', 'pull'])->default('send');
            $table->enum('status', ['running', 'succeeded', 'failed'])->default('running');
            $table->unsignedInteger('item_count')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->string('failure_reason', 1024)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'api_mode', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchagram_catalog_snapshots');
    }
};

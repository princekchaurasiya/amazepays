<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Each execution of a provider sync job (catalog refresh, stock refresh, reconciliation).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_sync_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('connection_id')->constrained('provider_connections')->cascadeOnUpdate()->cascadeOnDelete();
            $table->enum('job_type', [
                'catalog_sync', 'category_sync', 'stock_sync', 'price_sync',
                'balance_reconcile', 'payout_reconcile', 'refund_reconcile', 'other',
            ])->index();
            $table->enum('status', ['queued', 'running', 'succeeded', 'failed', 'cancelled'])->default('queued')->index();
            $table->unsignedInteger('records_fetched')->default(0);
            $table->unsignedInteger('records_created')->default(0);
            $table->unsignedInteger('records_updated')->default(0);
            $table->unsignedInteger('records_skipped')->default(0);
            $table->unsignedInteger('records_failed')->default(0);
            $table->text('last_error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['connection_id', 'job_type', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_sync_runs');
    }
};

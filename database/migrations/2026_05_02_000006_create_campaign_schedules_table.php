<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnUpdate()->cascadeOnDelete();

            $table->string('schedulable_type', 191);
            $table->unsignedBigInteger('schedulable_id');

            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->unsignedInteger('priority')->default(0);
            $table->boolean('is_enabled')->default(true)->index();

            $table->timestamps();

            $table->index(['tenant_id', 'schedulable_type', 'schedulable_id'], 'campaign_schedules_schedulable_idx');
            $table->index(
                ['tenant_id', 'is_enabled', 'start_at', 'end_at'],
                'campaign_schedules_tenant_window_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_schedules');
    }
};


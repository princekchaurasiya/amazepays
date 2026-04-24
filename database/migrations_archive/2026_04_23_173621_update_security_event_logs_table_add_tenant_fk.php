<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('security_event_logs', function (Blueprint $table) {
            if (Schema::hasColumn('security_event_logs', 'tenant_id')) {
                $table->index('tenant_id', 'security_event_logs_tenant_id_index');
                $table->foreign('tenant_id', 'security_event_logs_tenant_id_foreign')
                    ->references('id')
                    ->on('tenants')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('security_event_logs', function (Blueprint $table) {
            if (Schema::hasColumn('security_event_logs', 'tenant_id')) {
                $table->dropForeign('security_event_logs_tenant_id_foreign');
                $table->dropIndex('security_event_logs_tenant_id_index');
            }
        });
    }
};

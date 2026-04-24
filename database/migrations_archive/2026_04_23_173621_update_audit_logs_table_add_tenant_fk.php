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
        Schema::table('audit_logs', function (Blueprint $table) {
            if (Schema::hasColumn('audit_logs', 'tenant_id')) {
                $table->index('tenant_id', 'audit_logs_tenant_id_index');
                $table->foreign('tenant_id', 'audit_logs_tenant_id_foreign')
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
        Schema::table('audit_logs', function (Blueprint $table) {
            if (Schema::hasColumn('audit_logs', 'tenant_id')) {
                $table->dropForeign('audit_logs_tenant_id_foreign');
                $table->dropIndex('audit_logs_tenant_id_index');
            }
        });
    }
};

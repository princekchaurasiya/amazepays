<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('wallet_transactions', 'idempotency_key')) {
                $table->string('idempotency_key')->nullable()->unique()->after('description');
            }
            if (! Schema::hasColumn('wallet_transactions', 'reference_type')) {
                $table->string('reference_type')->nullable()->after('idempotency_key');
            }
            if (! Schema::hasColumn('wallet_transactions', 'reference_id')) {
                $table->unsignedBigInteger('reference_id')->nullable()->after('reference_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('wallet_transactions', 'reference_id')) {
                $table->dropColumn('reference_id');
            }
            if (Schema::hasColumn('wallet_transactions', 'reference_type')) {
                $table->dropColumn('reference_type');
            }
            if (Schema::hasColumn('wallet_transactions', 'idempotency_key')) {
                $table->dropUnique(['idempotency_key']);
                $table->dropColumn('idempotency_key');
            }
        });
    }
};

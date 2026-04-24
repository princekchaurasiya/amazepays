<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Wires cross-domain FKs that could not be declared inline because the target table didn't exist yet
 * at the time the source table was created (forward-references across waves).
 *
 * Wires:
 *   - products.hsn_sac_code_id -> hsn_sac_codes.id
 *   - payments.payment_instrument_id -> payment_instruments.id
 *   - payments.applied_bank_offer_id -> bank_offers.id
 *
 * Note: SQLite does not support ALTER TABLE ADD CONSTRAINT. On SQLite we skip adding the actual
 * FK constraint (the underlying column was defined at table-creation time), which is acceptable
 * because SQLite is only used by unit tests (phpunit.xml) and the column + index already exist.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->foreign('hsn_sac_code_id')
                ->references('id')
                ->on('hsn_sac_codes')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreign('payment_instrument_id')
                ->references('id')
                ->on('payment_instruments')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreign('applied_bank_offer_id')
                ->references('id')
                ->on('bank_offers')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['applied_bank_offer_id']);
            $table->dropForeign(['payment_instrument_id']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['hsn_sac_code_id']);
        });
    }
};

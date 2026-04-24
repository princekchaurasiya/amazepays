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
        Schema::table('invoices', function (Blueprint $table) {
            // Use a numeric column for computations while keeping legacy `amount` for backward compatibility.
            if (! Schema::hasColumn('invoices', 'amount_decimal')) {
                $table->decimal('amount_decimal', 12, 2)->nullable()->after('amount');
            }

            // Idempotency / integrity: invoice request + merchant order should be unique.
            $table->unique('request_id', 'invoices_request_id_unique');
            $table->unique('merchant_order_id', 'invoices_merchant_order_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique('invoices_merchant_order_id_unique');
            $table->dropUnique('invoices_request_id_unique');

            if (Schema::hasColumn('invoices', 'amount_decimal')) {
                $table->dropColumn('amount_decimal');
            }
        });
    }
};

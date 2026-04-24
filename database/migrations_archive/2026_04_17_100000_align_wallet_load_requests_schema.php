<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('wallet_load_requests')->where('status', 'under_review')->update(['status' => 'pending']);

        Schema::table('wallet_load_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('wallet_load_requests', 'reference_no')) {
                $table->string('reference_no', 100)->nullable()->after('amount');
            }
        });

        if (Schema::hasColumn('wallet_load_requests', 'utr_number')) {
            DB::table('wallet_load_requests')->update([
                'reference_no' => DB::raw('COALESCE(utr_number, bank_reference)'),
            ]);
        }

        Schema::table('wallet_load_requests', function (Blueprint $table) {
            if (Schema::hasColumn('wallet_load_requests', 'utr_number')) {
                $table->dropColumn(['utr_number', 'bank_reference']);
            }
        });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE wallet_load_requests CHANGE payment_method payment_mode VARCHAR(50) NOT NULL');
            if (Schema::hasColumn('wallet_load_requests', 'payment_proof_path')) {
                DB::statement('ALTER TABLE wallet_load_requests CHANGE payment_proof_path proof_file VARCHAR(255) NULL');
            }
            DB::statement("ALTER TABLE wallet_load_requests MODIFY COLUMN status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending'");
        } else {
            Schema::table('wallet_load_requests', function (Blueprint $table) {
                if (Schema::hasColumn('wallet_load_requests', 'payment_method')) {
                    $table->renameColumn('payment_method', 'payment_mode');
                }
                if (Schema::hasColumn('wallet_load_requests', 'payment_proof_path')) {
                    $table->renameColumn('payment_proof_path', 'proof_file');
                }
            });
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE wallet_load_requests MODIFY COLUMN status ENUM('pending', 'under_review', 'approved', 'rejected') NOT NULL DEFAULT 'pending'");
            if (Schema::hasColumn('wallet_load_requests', 'proof_file')) {
                DB::statement('ALTER TABLE wallet_load_requests CHANGE proof_file payment_proof_path VARCHAR(255) NULL');
            }
            DB::statement('ALTER TABLE wallet_load_requests CHANGE payment_mode payment_method VARCHAR(50) NOT NULL');
        } else {
            Schema::table('wallet_load_requests', function (Blueprint $table) {
                if (Schema::hasColumn('wallet_load_requests', 'proof_file')) {
                    $table->renameColumn('proof_file', 'payment_proof_path');
                }
                if (Schema::hasColumn('wallet_load_requests', 'payment_mode')) {
                    $table->renameColumn('payment_mode', 'payment_method');
                }
            });
        }

        Schema::table('wallet_load_requests', function (Blueprint $table) {
            $table->string('utr_number', 100)->nullable()->after('payment_method');
            $table->string('bank_reference', 100)->nullable()->after('utr_number');
        });

        if (Schema::hasColumn('wallet_load_requests', 'reference_no')) {
            DB::table('wallet_load_requests')->update([
                'utr_number' => DB::raw('reference_no'),
            ]);
        }

        Schema::table('wallet_load_requests', function (Blueprint $table) {
            $table->dropColumn('reference_no');
        });
    }
};

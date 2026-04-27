<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        if (! Schema::hasColumn('products', 'catalog_audience')) {
            Schema::table('products', function (Blueprint $table) {
                $table
                    ->enum('catalog_audience', ['b2c', 'b2b', 'both'])
                    ->nullable()
                    ->after('source_provider')
                    ->index();
            });
        }

        // Backfill for existing rows if legacy flags exist.
        if (
            Schema::hasColumn('products', 'catalog_audience')
            && Schema::hasColumn('products', 'is_b2b_only')
            && Schema::hasColumn('products', 'is_b2c_only')
        ) {
            DB::table('products')
                ->whereNull('catalog_audience')
                ->update([
                    'catalog_audience' => DB::raw(
                        "CASE
                            WHEN is_b2b_only = 1 THEN 'b2b'
                            WHEN is_b2c_only = 1 THEN 'b2c'
                            ELSE 'both'
                        END"
                    ),
                ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        if (Schema::hasColumn('products', 'catalog_audience')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropIndex(['catalog_audience']);
                $table->dropColumn('catalog_audience');
            });
        }
    }
};


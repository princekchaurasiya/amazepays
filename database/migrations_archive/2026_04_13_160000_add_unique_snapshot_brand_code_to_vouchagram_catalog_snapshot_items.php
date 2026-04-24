<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('
            DELETE t1 FROM vouchagram_catalog_snapshot_items t1
            INNER JOIN vouchagram_catalog_snapshot_items t2
            ON t1.snapshot_id = t2.snapshot_id
            AND t1.brand_product_code = t2.brand_product_code
            AND t1.id > t2.id
        ');

        $db = DB::getDatabaseName();

        $fkNames = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('TABLE_SCHEMA', $db)
            ->where('TABLE_NAME', 'vouchagram_catalog_snapshot_items')
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->pluck('CONSTRAINT_NAME');

        foreach ($fkNames as $name) {
            DB::statement('ALTER TABLE `vouchagram_catalog_snapshot_items` DROP FOREIGN KEY `'.$name.'`');
        }

        $indexNames = collect(DB::select('SHOW INDEX FROM `vouchagram_catalog_snapshot_items`'))
            ->pluck('Key_name')
            ->unique()
            ->values();

        foreach (['vg_cat_snap_items_snap_idx', 'vg_cat_snap_items_snap_code_idx'] as $keyName) {
            if ($indexNames->contains($keyName)) {
                DB::statement('ALTER TABLE `vouchagram_catalog_snapshot_items` DROP INDEX `'.$keyName.'`');
            }
        }

        $indexNamesAfter = collect(DB::select('SHOW INDEX FROM `vouchagram_catalog_snapshot_items`'))
            ->pluck('Key_name')
            ->unique();

        if (! $indexNamesAfter->contains('vg_cat_snap_items_snap_code_unq')) {
            Schema::table('vouchagram_catalog_snapshot_items', function (Blueprint $table) {
                $table->unique(['snapshot_id', 'brand_product_code'], 'vg_cat_snap_items_snap_code_unq');
            });
        }

        $fkStill = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('TABLE_SCHEMA', $db)
            ->where('TABLE_NAME', 'vouchagram_catalog_snapshot_items')
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->count();

        if ($fkStill === 0) {
            Schema::table('vouchagram_catalog_snapshot_items', function (Blueprint $table) {
                $table->foreign('snapshot_id')
                    ->references('id')
                    ->on('vouchagram_catalog_snapshots')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $db = DB::getDatabaseName();

        $fkNames = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('TABLE_SCHEMA', $db)
            ->where('TABLE_NAME', 'vouchagram_catalog_snapshot_items')
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->pluck('CONSTRAINT_NAME');

        foreach ($fkNames as $name) {
            DB::statement('ALTER TABLE `vouchagram_catalog_snapshot_items` DROP FOREIGN KEY `'.$name.'`');
        }

        $indexNames = collect(DB::select('SHOW INDEX FROM `vouchagram_catalog_snapshot_items`'))
            ->pluck('Key_name')
            ->unique();

        if ($indexNames->contains('vg_cat_snap_items_snap_code_unq')) {
            DB::statement('ALTER TABLE `vouchagram_catalog_snapshot_items` DROP INDEX `vg_cat_snap_items_snap_code_unq`');
        }

        Schema::table('vouchagram_catalog_snapshot_items', function (Blueprint $table) {
            $table->index('snapshot_id', 'vg_cat_snap_items_snap_idx');
            $table->index(['snapshot_id', 'brand_product_code'], 'vg_cat_snap_items_snap_code_idx');
        });

        Schema::table('vouchagram_catalog_snapshot_items', function (Blueprint $table) {
            $table->foreign('snapshot_id')
                ->references('id')
                ->on('vouchagram_catalog_snapshots')
                ->cascadeOnDelete();
        });
    }
};

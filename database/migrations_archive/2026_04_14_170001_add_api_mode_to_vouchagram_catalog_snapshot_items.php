<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vouchagram_catalog_snapshot_items', function (Blueprint $table) {
            $table->string('api_mode', 10)->default('send')->after('snapshot_id');
        });

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('
            UPDATE vouchagram_catalog_snapshot_items i
            INNER JOIN vouchagram_catalog_snapshots s ON s.id = i.snapshot_id
            SET i.api_mode = s.mode
        ');
    }

    public function down(): void
    {
        Schema::table('vouchagram_catalog_snapshot_items', function (Blueprint $table) {
            $table->dropColumn('api_mode');
        });
    }
};

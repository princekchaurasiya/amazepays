<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('
            UPDATE vouchagram_catalog_snapshots s
            SET item_count = (
                SELECT COUNT(*) FROM vouchagram_catalog_snapshot_items i WHERE i.snapshot_id = s.id
            )
        ');
    }

    public function down(): void
    {
        //
    }
};

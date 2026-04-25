<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop in FK-safe order.
        if (Schema::hasTable('slides')) {
            Schema::drop('slides');
        }

        if (Schema::hasTable('homepage_sections')) {
            Schema::drop('homepage_sections');
        }
    }

    public function down(): void
    {
        // Intentionally empty. The legacy tables are retired and not restored on rollback.
    }
};


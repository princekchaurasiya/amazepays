<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HSN (Harmonized System of Nomenclature) / SAC (Services Accounting Code) catalogue.
 * Each code maps to a tax category; actual percentage lives in tax_rates (versioned by effective_from/to).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hsn_sac_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16)->unique();
            $table->enum('type', ['hsn', 'sac'])->default('sac');
            $table->string('description', 512);
            $table->string('chapter', 64)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hsn_sac_codes');
    }
};

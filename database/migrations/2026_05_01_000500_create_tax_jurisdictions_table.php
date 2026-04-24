<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tax jurisdiction (country / state / union-territory) for routing correct tax rates.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_jurisdictions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16)->unique();
            $table->string('name');
            $table->enum('scope', ['country', 'state', 'union_territory', 'city'])->default('country');
            $table->string('country_code', 2);
            $table->string('state_code', 16)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['country_code', 'state_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_jurisdictions');
    }
};

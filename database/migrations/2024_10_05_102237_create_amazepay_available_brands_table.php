<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAmazepayAvailableBrandsTable extends Migration
{
    public function up()
    {
        Schema::create('amazepay_available_brands', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Name of the brand
            $table->string('slug')->unique(); // Unique slug for routing
            $table->string('logo')->nullable(); // Optional logo for the brand
            $table->decimal('order', 5, 2)->nullable(); // Nullable order column for sorting
            $table->timestamps(); // Created and updated timestamps
        });
    }

    public function down()
    {
        Schema::dropIfExists('amazepay_available_brands');
    }
}


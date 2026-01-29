<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQsProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('qs_products')) {
            Schema::create('qs_products', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedBigInteger('brand_id')->nullable();
                $table->integer('product_id')->nullable();
                $table->string('sku')->nullable();
                $table->integer('sku_limits')->nullable();
                $table->string('discounts')->nullable();
                $table->string('name')->nullable();
                $table->text('description')->nullable();
                $table->longText('price')->nullable();
                $table->integer('kycEnabled')->nullable();
                $table->text('additionalForm')->nullable();
                $table->text('metaInformation')->nullable();
                $table->string('type', 50)->nullable();
                $table->string('schedulingEnabled', 50)->nullable();
                $table->string('currency', 256)->nullable();
                $table->string('product_currency_code', 50)->nullable();
                $table->longText('images')->nullable();
                $table->string('custom_image')->nullable();
                $table->longText('tnc')->nullable();
                $table->longText('categories')->nullable();
                $table->longText('themes')->nullable();
                $table->string('customThemesAvailable', 50)->nullable();
                $table->string('handlingCharges', 50)->nullable();
                $table->string('reloadCardNumber', 50)->nullable();
                $table->string('expiry', 50)->nullable();
                $table->string('formatExpiry', 50)->nullable();
                $table->text('relatedProducts')->nullable();
                $table->string('storeLocatorUrl', 50)->nullable();
                $table->string('brandName', 50)->nullable();
                $table->string('etaMessage', 50)->nullable();
                $table->text('cpg')->nullable();
                $table->text('payout')->nullable();
                $table->string('allowedfulfillments', 50)->nullable();
                $table->string('url')->nullable();
                $table->string('minPrice')->nullable();
                $table->string('maxPrice')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
                $table->string('prdt_created_at', 50)->nullable();
                $table->string('prdt_updated_at', 50)->nullable();
                $table->integer('qs_category_id')->nullable();
                $table->unsignedBigInteger('amazepay_category_id')->nullable();
                $table->float('discount_percentage')->nullable();
                $table->decimal('CGST', 8, 2)->nullable();
                $table->decimal('SGST', 8, 2)->nullable();
                $table->decimal('IGST', 8, 2)->nullable();
                $table->string('slug')->nullable();
                $table->decimal('priority', 5, 2)->nullable();
                $table->decimal('secondary_priority', 5, 2)->nullable();
                $table->boolean('show_product')->default(true);
                $table->text('amazepay_how_to_redeem')->nullable();
                $table->text('amazepay_t_and_c')->nullable();
                $table->text('amazepay_product_description')->nullable();
                $table->boolean('is_special_sku')->default(false);
                $table->boolean('out_of_stock')->default(false);
                
                // Foreign keys - only add if referenced tables exist
                if (Schema::hasTable('amazepay_categories')) {
                    $table->foreign('amazepay_category_id')->references('id')->on('amazepay_categories')->onDelete('cascade');
                }
                if (Schema::hasTable('amazepay_available_brands')) {
                    $table->foreign('brand_id')->references('id')->on('amazepay_available_brands')->onDelete('cascade');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('qs_products');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated products schema (legacy voucher catalog + admin catalog + SEO).
 * Previously split across many small migrations; merged for greenfield installs.
 */
class CreateProductsTable extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('products')) {
            return;
        }

        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->string('source_provider', 50)->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->string('sync_status', 20)->default('pending');
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->integer('product_id')->nullable();
            $table->string('sku')->nullable();
            $table->integer('sku_limits')->nullable();
            $table->string('discounts')->nullable();
            $table->string('name')->nullable();
            $table->string('product_name')->nullable();
            $table->text('description')->nullable();
            $table->longText('price')->nullable();
            $table->decimal('selling_price', 12, 2)->nullable();
            $table->decimal('mrp', 12, 2)->nullable();
            $table->decimal('denomination', 12, 2)->nullable();
            $table->decimal('gst_rate', 5, 2)->nullable();
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
            $table->unsignedBigInteger('synced_category_id')->nullable();
            $table->float('discount_percentage')->nullable();
            $table->decimal('CGST', 8, 2)->nullable();
            $table->decimal('SGST', 8, 2)->nullable();
            $table->decimal('IGST', 8, 2)->nullable();
            $table->string('slug')->nullable();
            $table->decimal('priority', 5, 2)->nullable();
            $table->decimal('display_order', 5, 2)->nullable();
            $table->boolean('show_product')->default(true);
            $table->longText('how_to_redeem')->nullable();
            $table->longText('terms_and_conditions')->nullable();
            $table->longText('custom_description')->nullable();
            $table->timestamp('content_customized_at')->nullable();
            $table->foreignId('content_customized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_special_sku')->default(false);
            $table->boolean('out_of_stock')->default(false);
            $table->string('out_of_stock_comment')->nullable();

            // SEO (admin + API / mobile)
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords', 500)->nullable();
            $table->string('og_image', 500)->nullable();
            $table->string('canonical_url', 500)->nullable();
            $table->unsignedTinyInteger('seo_score')->nullable();
            $table->timestamp('seo_edited_at')->nullable();
            $table->foreignId('seo_edited_by')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unique('sku', 'products_sku_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
}

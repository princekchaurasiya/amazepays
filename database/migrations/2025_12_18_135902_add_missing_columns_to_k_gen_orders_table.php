<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('k_gen_orders')) {
            Schema::table('k_gen_orders', function (Blueprint $table) {
            // User relationship
            if (!Schema::hasColumn('k_gen_orders', 'user_id')) {
                $table->foreignId('user_id')
                      ->nullable()
                      ->after('id')
                      ->constrained('users')
                      ->onDelete('set null');
            }
            
            // Missing order fields
            if (!Schema::hasColumn('k_gen_orders', 'payable_amount')) {
                $table->decimal('payable_amount', 10, 2)->default(0)->after('mrp');
            }
            
            if (!Schema::hasColumn('k_gen_orders', 'selling_price')) {
                $table->decimal('selling_price', 10, 2)->default(0)->nullable()->after('payable_amount');
            }
            
            if (!Schema::hasColumn('k_gen_orders', 'status')) {
                $table->string('status')->default('PENDING_PAYMENT')->after('selling_price');
            }
            
            if (!Schema::hasColumn('k_gen_orders', 'payment_status')) {
                $table->string('payment_status')->nullable()->after('status');
            }
            
            if (!Schema::hasColumn('k_gen_orders', 'fulfillment_status')) {
                $table->string('fulfillment_status')->nullable()->after('payment_status');
            }
            
            if (!Schema::hasColumn('k_gen_orders', 'vouchers')) {
                $table->json('vouchers')->nullable()->after('api_response');
            }
            
            // Indexes
            $table->index(['user_id', 'status', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('k_gen_orders', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn([
                'user_id', 'payable_amount', 'selling_price', 
                'status', 'payment_status', 'fulfillment_status', 'vouchers'
            ]);
        });
    }
};

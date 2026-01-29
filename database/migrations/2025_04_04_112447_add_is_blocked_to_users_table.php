<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsBlockedToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'is_blocked')) {
                    $table->boolean('is_blocked')->default(false);
                }
                if (!Schema::hasColumn('users', 'can_transact')) {
                    $table->boolean('can_transact')->default(true);
                }
                if (!Schema::hasColumn('users', 'restricted_features')) {
                    $table->json('restricted_features')->nullable();
                }
                if (!Schema::hasColumn('users', 'restriction_reason')) {
                    $table->text('restriction_reason')->nullable();
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
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_blocked');
            $table->dropColumn('can_transact');
            $table->dropColumn('restricted_features');
            $table->dropColumn('restriction_reason');
        });
    }
}

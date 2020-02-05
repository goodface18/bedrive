<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateUsersTableToV2 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'name')) {
                $table->string('name')->nullable()->change();
                $table->renameColumn('name', 'username');
            }

            if ( ! Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->text('permissions')->nullable();
            }

            if ( ! Schema::hasColumn('users', 'card_brand')) {
                $table->string('card_brand')->nullable();
            }

            if ( ! Schema::hasColumn('users', 'card_last_four')) {
                $table->string('card_last_four')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('username', 'name');
            $table->dropColumn('first_name');
            $table->dropColumn('last_name');
            $table->dropColumn('permissions');
            $table->dropColumn('card_brand');
            $table->dropColumn('card_last_four');
        });
    }
}

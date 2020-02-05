<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAvailableSpaceToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('users', 'available_space')) return;

        Schema::table('users', function (Blueprint $table) {
            $table->bigInteger('available_space')->nullable()->unsigned();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if ( ! Schema::hasColumn('users', 'available_space')) return;

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('space_available');
        });
    }
}

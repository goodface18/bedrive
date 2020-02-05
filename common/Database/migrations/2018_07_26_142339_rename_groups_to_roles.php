<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenameGroupsToRoles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if ( ! Schema::hasTable('roles')) {
            Schema::table('groups', function (Blueprint $table) {
                $table->rename('roles');
            });
        }

       if ( ! Schema::hasTable('user_role')) {
           Schema::table('user_group', function (Blueprint $table) {
               $table->rename('user_role');
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
        Schema::table('roles', function (Blueprint $table) {
            $table->rename('groups');
        });

        Schema::table('user_role', function (Blueprint $table) {
            $table->rename('user_group');
        });
    }
}

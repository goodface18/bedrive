<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTypeAndTitleColumnsToPagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pages', function (Blueprint $table) {
            if ( ! Schema::hasColumn('pages', 'type')) {
                $table->string('type', 20)->index()->default('default')->after('slug');
            }

            if ( ! Schema::hasColumn('pages', 'title')) {
                $table->string('title')->nullable()->after('id');
            } else {
                $table->string('title')->nullable()->change();
            }

            $table->text('meta')->nullable()->after('slug');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('type');
            $table->dropColumn('title');
            $table->dropColumn('meta');
        });
    }
}

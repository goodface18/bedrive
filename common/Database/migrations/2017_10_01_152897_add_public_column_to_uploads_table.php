<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPublicColumnToUploadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('uploads', function (Blueprint $table) {
            $table->boolean('public')->default(0)->index();

            // set collation to latin1_bin for maximum encoded string length
            $pathColumn = $table->string('path')->nullable();
            $pathColumn->collation = 'latin1_bin';
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('uploads', function (Blueprint $table) {
            $table->dropColumn('public');
            $table->dropColumn('path');
        });
    }
}

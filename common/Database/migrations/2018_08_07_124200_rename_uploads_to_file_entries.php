<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenameUploadsToFileEntries extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('file_entries')) return;

        Schema::table('uploads', function (Blueprint $table) {
            $table->rename('file_entries');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('file_entries', function (Blueprint $table) {
            $table->rename('uploads');
        });
    }
}

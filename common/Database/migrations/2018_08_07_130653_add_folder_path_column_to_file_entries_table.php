<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFolderPathColumnToFileEntriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('file_entries', 'path')) return;

        Schema::table('file_entries', function (Blueprint $table) {
            $table->string('path')->nullable()->index();
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
            $table->dropColumn('path');
        });
    }
}

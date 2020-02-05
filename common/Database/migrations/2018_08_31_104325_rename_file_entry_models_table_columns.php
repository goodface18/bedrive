<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenameFileEntryModelsTableColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('file_entry_models', function (Blueprint $table) {
            $table->renameColumn('upload_id', 'file_entry_id');
            $table->renameColumn('uploadable_id', 'model_id');
            $table->renameColumn('uploadable_type', 'model_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('file_entry_models', function (Blueprint $table) {
            $table->renameColumn('file_entry_id', 'upload_id');
            $table->renameColumn('model_id', 'uploadable_id');
            $table->renameColumn('model_type', 'uploadable_type');
        });
    }
}

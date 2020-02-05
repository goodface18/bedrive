<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MergeFilesAndFoldersTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('file_entries', function(Blueprint $table) {
            $column = $table->string('path', 255)->nullable()->index();
            $column->collation = 'latin1_bin';

            $table->string('public_path', 255)->nullable();
            $table->string('type', 20)->nullable()->index();
            $table->string('extension', 10)->nullable();
            $table->boolean('public')->default(0)->index();

            $table->dropColumn('attach_id');
            $table->dropColumn('share_id');

            $table->string('mime', 100)->nullable()->change();
            $table->string('file_name', 255)->change();
            $table->bigInteger('file_size')->nullable()->unsigned()->change();

            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('files_entries', function(Blueprint $table) {
            $table->removeColumn('type');
            $table->string('mime', 50)->nullable();
            $table->string('uuid', 20)->unique()->change();
        });
    }
}

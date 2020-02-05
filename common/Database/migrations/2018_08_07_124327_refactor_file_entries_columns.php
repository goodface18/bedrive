<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RefactorFileEntriesColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // refactoring was already done via another (app specific) migration
        if (Schema::hasColumn('file_entries', 'public_path')) return;

        Schema::table('file_entries', function (Blueprint $table) {
            $table->bigInteger('file_size')->unsigned()->nullable()->change();
            $table->integer('parent_id')->nullable()->index();
            $table->string('description', 150)->nullable();
            $table->string('mime', 100)->nullable()->change();
            $table->string('extension', 10)->nullable()->change();
            $table->string('password', 50)->nullable();
            $table->string('type', 20)->nullable()->index();
            $table->timestamp('deleted_at')->nullable()->index();
            $table->dropColumn('url');
            $table->dropColumn('thumbnail_url');
            $table->renameColumn('path', 'public_path');
            $table->string('user_id')->index()->nullable()->change();

            $table->index('updated_at');
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
            $table->dropColumn('parent_id');
            $table->dropColumn('description');
            $table->dropColumn('password');
            $table->dropColumn('deleted_at');
        });
    }
}

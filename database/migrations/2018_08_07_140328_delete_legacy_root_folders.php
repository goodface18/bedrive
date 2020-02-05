<?php

use Illuminate\Database\Migrations\Migration;

class DeleteLegacyRootFolders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // get ids
        $ids = DB::table('folders')->where('name', 'root')->pluck('id');

        // set "parent_id" from root folder id to "null"
        DB::table('file_entries')->whereIn('parent_id', $ids)->update(['parent_id' => null]);

        // delete root folders
        DB::table('folders')->whereIn('id', $ids)->delete('');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}

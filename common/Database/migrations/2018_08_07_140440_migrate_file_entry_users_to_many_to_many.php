<?php

use Illuminate\Database\Eloquent\Collection;
use Common\Files\FileEntry;
use Illuminate\Database\Migrations\Migration;

class MigrateFileEntryUsersToManyToMany extends Migration
{
    /**
     * migrate file entries => user from "one to one" to "many to many"
     *
     * @return void
     */
    public function up()
    {
        FileEntry::select('id', 'user_id')->orderBy('id')->chunk(50, function(Collection $entries) {
            $records = $entries->map(function(FileEntry $entry) {
                return ['file_entry_id' => $entry->id, 'user_id' => $entry->user_id, 'owner' => 1];
            });

            DB::table('user_file_entry')->insert($records->toArray());
        });
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

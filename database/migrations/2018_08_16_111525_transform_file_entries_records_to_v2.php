<?php

use App\FileEntry;
use Common\Files\Traits\GetsEntryTypeFromMime;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;

class TransformFileEntriesRecordsToV2 extends Migration
{
    use GetsEntryTypeFromMime;

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        FileEntry::orderBy('id', 'desc')
            ->with('parent')
            ->chunk(50, function(Collection $entries) {
                $entries->each(function(FileEntry $entry) {
                    $pathinfo = pathinfo($entry->file_name);

                    $entry->update([
                        // generate "type" column value based on mime
                        'type' => $this->generateType($entry),

                        // remove extension from "file_name"
                        'file_name' => $pathinfo['filename'],

                        // fill extension column
                        'extension' => isset($pathinfo['extension']) ? $pathinfo['extension'] : null,

                        // generate "path" column value
                        'path' => $this->generatePath($entry)
                    ]);
                });
            });
    }

    private function generateType(FileEntry $entry)
    {
        if ($entry->type === 'folder') return 'folder';

        if ( ! $entry->mime) return null;

        return $this->getTypeFromMime($entry->mime);
    }

    private function generatePath(FileEntry $entry)
    {
        if ($entry->path) return $entry->path;

        if ( ! $entry->parent) {
            return $entry->id;
        }

        return $entry->parent->path . '/' . $entry->id;
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

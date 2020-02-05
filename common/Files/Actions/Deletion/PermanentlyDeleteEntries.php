<?php

namespace Common\Files\Actions\Deletion;

use DB;
use Storage;
use Common\Files\FileEntry;
use Illuminate\Support\Collection;

class PermanentlyDeleteEntries extends SoftDeleteEntries
{
    /**
     * Permanently delete file entries, related records and files from disk.
     *
     * @param Collection $entries
     * @return bool|null
     */
    protected function delete(Collection $entries)
    {
        $entries = $this->loadChildEntries($entries, true);
        $this->deleteFiles($entries);
        return $this->deleteEntries($entries);
    }

    /**
     * Delete file entries from database.
     *
     * @param Collection $entries
     * @return bool|null
     */
    private function deleteEntries(Collection $entries) {
        $entryIds = $entries->pluck('id');

        // detach users
        DB::table('user_file_entry')->whereIn('file_entry_id', $entryIds)->delete();

        // detach tags
        DB::table('taggables')->where('taggable_type', FileEntry::class)->whereIn('taggable_id', $entryIds)->delete();

        return $this->entry->whereIn('id', $entries->pluck('id'))->forceDelete();
    }

    /**
     * Delete files from disk.
     *
     * @param Collection $entries
     * @return Collection
     */
    private function deleteFiles(Collection $entries)
    {
        return $entries->filter(function (FileEntry $entry) {
            return $entry->type !== 'folder';
        })->each(function(FileEntry $entry) {
           $entry->getDisk()->deleteDir($entry->file_name);
        });
    }
}
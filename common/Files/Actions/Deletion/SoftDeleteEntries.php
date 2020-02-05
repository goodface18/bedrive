<?php

namespace Common\Files\Actions\Deletion;

use Common\Files\FileEntry;
use Illuminate\Support\Collection;
use Common\Files\Traits\LoadsAllChildEntries;

class SoftDeleteEntries
{
    use LoadsAllChildEntries;

    /**
     * @var FileEntry
     */
    protected $entry;

    /**
     * @param FileEntry $entry
     */
    public function __construct(FileEntry $entry)
    {
        $this->entry = $entry;
    }

    /**
     * @param Collection|array $entryIds
     * @return void
     */
    public function execute($entryIds)
    {
        collect($entryIds)->chunk(50)->each(function($ids) {
            $entries = $this->entry->withTrashed()->whereIn('id', $ids)->get();
            $this->delete($entries);
        });
    }

    /**
     * Move specified entries to "trash".
     *
     * @param Collection $entries
     * @return bool|null
     */
    protected function delete(Collection $entries)
    {
        $entries = $this->loadChildEntries($entries);
        return $this->entry->whereIn('id', $entries->pluck('id'))->delete();
    }
}
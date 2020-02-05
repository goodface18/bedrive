<?php

namespace Common\Files\Actions\Deletion;

class RestoreEntries extends SoftDeleteEntries
{
    public function execute($entryIds)
    {
        $entries = $this->entry->onlyTrashed()->whereIn('id', $entryIds)->get();
        $entries = $this->loadChildEntries($entries);
        return $this->entry->whereIn('id', $entries->pluck('id'))->restore();
    }
}
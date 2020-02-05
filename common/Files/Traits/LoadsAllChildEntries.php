<?php

namespace Common\Files\Traits;

use Common\Files\FileEntry;
use Illuminate\Support\Collection;

trait LoadsAllChildEntries
{
    /**
     * Fetch all children of specified entries.
     *
     * @param Collection $entries
     * @param bool $withTrashed
     * @return Collection
     */
    protected function loadChildEntries(Collection $entries, $withTrashed = false)
    {
        $builder = FileEntry::select('id', 'file_name', 'type');

        // load parent entries, if we got only IDs passed in
        if (is_numeric($entries->first())) {
            $entries = FileEntry::whereIn('id', $entries)->get();
        }

        if ($withTrashed) {
            $builder->withTrashed();
        }

        $entries->each(function (FileEntry $entry) use ($builder) {
            if ($entry->type === 'folder') {
                $path = $entry->getOriginal('path');
                $builder->orWhere('path', 'LIKE', "$path/%");
            }
        });

        //only fetch children if any "where" constraints were applied
        if (count($builder->getQuery()->wheres)) {
            $children = $builder->get();
            $entries =  $entries->merge($children);
        }

        return $entries;
    }
}
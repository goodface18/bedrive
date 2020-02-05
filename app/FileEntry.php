<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Common\Files\FileEntry as CommonFileEntry;

/**
 * @method static \Illuminate\Database\Query\Builder|FileEntry onlyStarred()
 * @method static \Illuminate\Database\Query\Builder|FileEntry sharedWithUserOnly($userId)
 */
class FileEntry extends CommonFileEntry
{
    protected $table = 'file_entries';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function labels()
    {
        return $this->tags()->where('tags.type', 'label');
    }

    /**
     * Get only entries that are not children of another entry.
     *
     * @param Builder $builder
     * @return Builder
     */
    public function scopeRootOnly(Builder $builder) {
        return $builder->where('parent_id', null);
    }

    /**
     * Get only entries that are starred.
     * Only show entries from root or entries whose parent is not starred.
     *
     * @param Builder $builder
     * @return Builder
     */
    public function scopeOnlyStarred(Builder $builder) {
        return $builder->whereHas('labels', function($query) {
            return $query->where('tags.name', 'starred');
        })->where(function($query) {
            $query->rootOnly()->orWhereDoesntHave('parent', function($query) {
                return $query->whereHas('labels', function($q) {
                    return $q->where('tags.name', 'starred');
                });
            });
        });
    }

    /**
     * @param Builder $query
     * @param int $userId
     * @return Builder
     */
    public function scopeSharedWithUserOnly(Builder $query, $userId)
    {
        // get only entries which user does not own (did not upload)
        return $query->whereNotOwner($userId)

            // get all entries that are in root folder,
            // also get shared entries, whose parent folder is not shared
            // "folder/file.txt", if "file.txt" is shared and "folder" is not shared, get "file.txt"
            ->whereDoesntHave('parent', function(Builder $query) use($userId)  {
                return $query->whereNotOwner($userId);
            });
    }
}

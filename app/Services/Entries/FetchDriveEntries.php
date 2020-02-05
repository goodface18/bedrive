<?php

namespace App\Services\Entries;

use DB;
use App\FileEntry;
use Illuminate\Support\Arr;
use Common\Database\Paginator;
use Illuminate\Database\Eloquent\Builder;

class FetchDriveEntries
{
    /**
     * @var FileEntry
     */
    private $entry;

    /**
     * @var Builder|FileEntry
     */
    private $query;

    /**
     * @param FileEntry $entry
     */
    public function __construct(FileEntry $entry)
    {
        $this->entry = $entry;
    }

    /**
     * Fetch all file entries matching specified params.
     *
     * @param array $params
     * @return array
     */
    public function execute($params)
    {
        $paginator = (new Paginator($this->entry));
        $this->query = $paginator->query();
        $userId = $params['userId'];
        $trashedOnly = Arr::get($params, 'deletedOnly');
        $starredOnly = Arr::get($params, 'starredOnly');
        $recentOnly = Arr::get($params, 'recentOnly');
        $sharedOnly = Arr::get($params, 'sharedOnly');
        $searching = Arr::get($params, 'query') || Arr::get($params, 'type');

        // folders should always be first
        $this->query->orderBy(DB::raw('type = "folder"'), 'desc')
            ->with('users', 'tags');

        $folder = $this->getFolder($params); // "null" will indicate root folder

        // fetch only entries that are children of specified parent,
        // in trash, show files/folders if their parent is not trashed
        if ( ! $trashedOnly && ! $starredOnly && ! $recentOnly && ! $searching && ! $sharedOnly) {
            $this->query->where('parent_id', $folder ? $folder->id : null);
        }

        // shares page, get only entries user has access to, but did not upload
        if ($sharedOnly) {
            $this->query->sharedWithUserOnly($userId);

        // listing children of specific folder, or searching
        // get all children of folder that users has access to
        } else if ($folder || $searching) {
            $this->query->whereUser($userId);

        // root folder or other pages (recent, trash etc.)
        // get only entries that user has created
        } else {
            $this->query->whereOwner($userId);
        }

        // fetch only entries that are in trash
        if ($trashedOnly) {
            $this->query->onlyTrashed()->whereRootOrParentNotTrashed();
        }

        // fetch only files, if we need recent entries
        if ($recentOnly) {
            $this->query->where('type', '!=', 'folder');
        }

        // fetch only entries that are starred (favorited)
        if ($starredOnly) {
            $this->query->onlyStarred();
        }

        // fetch only entries matching specified type (image, text, audio etc)
        if ($type = Arr::get($params, 'type')) {
            $this->query->where('type', $type);
        }

        // make sure "public" uploads are not fetched
        $this->query->where('public', 0);

        if ($searchTerm = Arr::get($params, 'query')) {
            $paginator->searchCallback = function (Builder $q) use($searchTerm) {
                $q->where('name', 'like', "$searchTerm%")->orWhere('description', 'like', "$searchTerm%");
            };
        }

        $params['perPage'] = 50;

        $results = $paginator->paginate($params)->toArray();

        if ($folder) $results['folder'] = $folder;

        return $results;
    }

    /**
     * @param array $params
     * @return FileEntry|null
     */
    protected function getFolder($params)
    {
        // no folderId specified or it's "root" folder
        $folderId = Arr::get($params, 'folderId');
        if ( ! $folderId || $folderId === 'root') return null;

        // it's a folder hash, need to decode it
        if ((int) $folderId === 0) {
            $folderId = $this->entry->decodeHash($folderId);
        } else {
            $folderId = (int) $folderId;
        }

        return $this->entry->with('users')->find($folderId);
    }
}
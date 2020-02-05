<?php

namespace App\Http\Controllers;

use Auth;
use Storage;
use App\FileEntry;
use Illuminate\Http\Request;
use Common\Core\Controller;
use Illuminate\Database\Eloquent\Collection;

class CopyEntriesController extends Controller
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var FileEntry
     */
    private $entry;

    /**
     * CopyEntriesController constructor.
     *
     * @param Request $request
     * @param FileEntry $entry
     */
    public function __construct(Request $request, FileEntry $entry)
    {
        $this->request = $request;
        $this->entry = $entry;
    }

    /**
     * Make copies of all specified entries and their children.
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function copy()
    {
        // TODO: maybe limit to 100 or so entries

        $this->validate($this->request, [
            'entryIds' => 'required|array',
            'entryIds.*' => 'required|integer',
        ]);

        $entryIds = $this->request->get('entryIds');

        // TODO: use "show" policy method when it supports multiple entry IDs
        $this->authorize('index', [FileEntry::class, $entryIds]);

        $copies = $this->copyEntries($entryIds);

        return $this->success(['entries' => $copies]);
    }

    /**
     * @param array|\Illuminate\Support\Collection $entryIds
     * @param int|null $parentId
     * @return Collection
     */
    private function copyEntries($entryIds, $parentId = null)
    {
        $copies = collect();

        foreach ($this->entry->with('owner')->whereIn('id', $entryIds)->cursor() as $entry) {
            if ($entry->type === 'folder') {
                $copies[] = $this->copyFolderEntry($entry, $parentId);
            } else {
                $copies[] = $this->copyFileEntry($entry, $parentId);
            }
        }

        return $copies;
    }

    /**
     * @param FileEntry $original
     * @param int|null $parentId
     * @return FileEntry
     */
    private function copyFileEntry(FileEntry $original, $parentId = null)
    {
        $copy = $this->copyModel($original, $parentId);
        $this->copyFile($original, $copy);

        return $copy;
    }

    /**
     * @param FileEntry $original
     * @param int|null $parentId
     * @return FileEntry
     */
    private function copyFolderEntry(FileEntry $original, $parentId = null)
    {
        $copy = $this->copyModel($original, $parentId);
        $this->copyChildEntries($copy, $original);

        return $copy;
    }

    /**
     * @param FileEntry $copy
     * @param FileEntry $original
     */
    private function copyChildEntries(FileEntry $copy, FileEntry $original)
    {
        $entryIds = $this->entry->where('parent_id', $original->id)->pluck('id');

        if ( ! $entryIds->isEmpty()) {
            $this->copyEntries($entryIds, $copy->id);
        }
    }

    /**
     * @param FileEntry $original
     * @param int|null $parentId
     * @return FileEntry
     */
    private function copyModel(FileEntry $original, $parentId = null)
    {
        $newName = $original->name;
        $newOwnerId = $this->getCopyOwnerId();
        $copyingIntoSameDrive = $newOwnerId === $original->getOwner()->id;

        // if not parent ID is specified and we are copying into the
        // same users drive, we can copy into the same folder as original
        if ( ! $parentId && $copyingIntoSameDrive) {
            $parentId = $original->parent_id;
        }

        // if we are copying into same folder, add " - Copy" to the end of copies name
        if ($parentId === $original->parent_id && $copyingIntoSameDrive) {
            $newName = "$original->name - Copy";
        }

        /**
         * @var $copy FileEntry
         */
        $copy = $original->replicate();
        $copy->name = $newName;
        $copy->path = null;
        $copy->file_name = str_random(40);
        $copy->parent_id = $parentId;
        $copy->save();

        $copy->generatePath();

        // set owner
        $copy->users()->attach($newOwnerId, ['owner' => true]);

        $copy->load('users');

        return $copy;
    }

    /**
     * @param FileEntry $original
     * @param FileEntry $copy
     */
    private function copyFile(FileEntry $original, FileEntry $copy)
    {
        //TODO: update this once thumbnails or multiple file versions as stored
        $original->getDisk()->copy("$original->file_name/$original->file_name", "$copy->file_name/$copy->file_name");
    }

    /**
     * Get user to which entry copies should be attached.
     *
     * @return int
     */
    private function getCopyOwnerId()
    {
        return Auth::user()->id;
    }
}

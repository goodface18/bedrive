<?php

namespace Common\Files\Actions;

use App\User;
use Auth;
use Common\Files\Events\FileEntryCreated;
use Common\Files\FileEntry;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;

class CreateFileEntry
{
    /**
     * @var FileEntry
     */
    private $entry;

    /**
     * @param FileEntry $entry
     */
    public function __construct(FileEntry $entry)
    {
        $this->entry = $entry;
    }

    /**
     * @param UploadedFile|array $fileOrData
     * @param $extra
     * @return FileEntry
     */
    public function execute($fileOrData, $extra)
    {
        if (is_array($fileOrData)) {
            $data = $fileOrData;
        } else {
            $data = app(UploadedFileToArray::class)->execute($fileOrData);
        }

        // merge extra data specified by user
        $data = array_merge($data, [
            'path' => Arr::get($extra, 'path'),
            'parent_id' => Arr::get($extra, 'parent_id'),
            'public_path' => Arr::get($extra, 'public_path'),
            'public' => Arr::get($extra, 'public_path') ? 1 : 0
        ]);

        // public files will be stored with extension
        if ($data['public']) {
            $data['file_name'] = $data['file_name'] . '.' . $data['extension'];
        }

        $userId = Arr::get($extra, 'user_id', Auth::id());
        $entries = collect();

        if (Arr::get($data, 'path')) {
            $entries = $entries->merge($this->createPath($data['path'], $data['parent_id'], $userId));
            $parent = $entries->last();
            if ($parent) $data['parent_id'] = $parent->id;
        }

        $fileEntry = $this->entry->create($data);

        if ( ! Arr::get($data, 'public')) {
            $fileEntry->generatePath();
        }

        $entries->push($fileEntry);

        $entryIds = $entries->mapWithKeys(function($entry) {
            return [$entry->id => ['owner' => 1]];
        })->toArray();

        User::find($userId)->entries()->syncWithoutDetaching($entryIds);

        if (isset($parent) && $parent) {
            $fileEntry->setRelation('parent', $parent);
        } else {
            $fileEntry->load('parent');
        }

        return $fileEntry;
    }

    /**
     * @param string $path
     * @param integer|null $parentId
     * @param integer $userId
     * @return \Illuminate\Support\Collection
     */
    private function createPath($path, $parentId, $userId)
    {
        $path = collect(explode('/', $path));
        $path = $path->filter(function($name) {
            return $name && ! str_contains($name, '.');
        });

        if ($path->isEmpty()) return $path;

        return $path->reduce(function($parents, $name) use($parentId, $userId) {
            if ( ! $parents) $parents = collect();
            $parent = $parents->last();

            $values = [
                'type' => 'folder',
                'name' => $name,
                'file_name' => $name,
                'parent_id' => $parent ? $parent->id : $parentId
            ];

            // check if user already has a folder with that name and parent
            $folder = $this->entry->where($values)
                ->whereOwner($userId)
                ->first();

            if ( ! $folder) {
                $folder = $this->entry->create($values);
                $folder->generatePath();

                // make sure new folder gets attached to all
                // users who have access to the parent folder
                event(new FileEntryCreated($folder));
                $folder->load('users');
            }

            return $parents->push($folder);
        });
    }
}
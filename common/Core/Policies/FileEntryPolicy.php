<?php

namespace Common\Core\Policies;

use Common\Auth\BaseUser;
use Common\Files\FileEntry;
use Illuminate\Support\Arr;
use Illuminate\Auth\Access\HandlesAuthorization;
use Request;

class FileEntryPolicy
{
    use HandlesAuthorization;

    /**
     * Check if current user can view specified entries.
     *
     * @param BaseUser $user
     * @param array $entryIds
     * @param int $userId
     * @return bool
     */
    public function index(BaseUser $user, array $entryIds = null, $userId = null)
    {
        // user has permissions to view all entries
        if ($user->hasPermission('files.view')) {
            return true;
        }

        // check if all entries of specified user can be viewed
        if ( ! $entryIds && (int) $userId === $user->id) {
            return true;
        }

        // check if specific entries can be viewed by user
        return $this->userHasPermission($user, 'view', $entryIds);
    }

    public function show(BaseUser $user, FileEntry $entry)
    {
        // allow access via preview token
        if ($entry->preview_token && $entry->preview_token === Request::get('preview_token')) {
            return true;
        }

        return $user->hasPermission('files.view') || $this->userHasPermission($user, 'view', [$entry->id]);
    }

    /**
     * Check if user can create entry.
     *
     * @param BaseUser $user
     * @param int $parentId
     * @return bool
     */
    public function store(BaseUser $user, $parentId = null)
    {
        //check if user can modify parent entry (if specified)
        if ($parentId) {
            return $this->userHasPermission($user, 'edit', [$parentId]);
        }

        return $user->hasPermission('files.create');
    }

    public function update(BaseUser $user, array $entryIds)
    {
        return $user->hasPermission('files.update') || $this->userHasPermission($user, 'edit', $entryIds);
    }

    public function destroy(BaseUser $user, array $entryIds)
    {
        if ( ! $entryIds || $user->hasPermission('files.delete')) {
            return true;
        }

        //check if user owns all of the specified entries
        $count = $user->entries()
            ->withTrashed()
            ->whereIn('file_entries.id', $entryIds)
            ->wherePivot('owner', true)
            ->count();

        return $count === count($entryIds);
    }

    private function userHasPermission(BaseUser $user, $permission, $entryIds)
    {
        if ( ! $entryIds || ! $user->id) {
            return false;
        }
        
        // check if user has edit permissions for all specified entries
        $entries = $user->entries()
            ->withPivot(['owner', 'permissions'])
            ->whereIn('file_entries.id', $entryIds)
            ->get();

        $entriesUserHasPermissionsFor = count(array_filter($entryIds, function($entryId) use($entries, $permission) {
            $entry = $entries->find($entryId);

            //user has no access to this entry at all
            if ( ! $entry) return false;

            //user is the owner of this entry
            if ($entry->pivot->owner) return true;

            // user was granted specified permission by file owner
            return Arr::get($entry->pivot->permissions, $permission);
        }));

        $allEntries = count($entryIds);

        return $entriesUserHasPermissionsFor === $allEntries;
    }
}

<?php

namespace App\Listeners;

use Common\Files\FileEntryUser;
use Common\Files\Events\FileEntryCreated;
use App\Services\Shares\UpdateEntryUsers;

class AttachUsersToNewlyUploadedFile
{
    /**
     * @var UpdateEntryUsers
     */
    private $action;

    /**
     * Create the event listener.
     *
     * @param UpdateEntryUsers $action
     */
    public function __construct(UpdateEntryUsers $action)
    {
        $this->action = $action;
    }

    /**
     * Attach all users that have access to entries parent folder to entry.
     *
     * @param FileEntryCreated $event
     * @return void
     */
    public function handle(FileEntryCreated $event)
    {
        $entry = $event->fileEntry;

        if ($entry->parent && $entry->parent->users->count() > 1) {
            $users = $entry->parent->users
                ->filter(function(FileEntryUser $user) use($entry) {
                    $entryUser = $entry->users->find($user->id);
                    // if user already owns this entry, skip them
                    return ! $entryUser || ! $entryUser->owns_entry;
                })->map(function(FileEntryUser $user) {
                    return ['id' => $user->id, 'permissions' => $user->owns_entry ? $this->getFullPermissions() : $user->entry_permissions];
                })->toArray();

            $this->action->execute($users, [$entry]);
        }
    }

    private function getFullPermissions() {
        return ['edit' => true, 'view' => true, 'download' => true];
    }
}

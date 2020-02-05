<?php

namespace App\Listeners;

use App\ShareableLink;
use Common\Files\Events\FileEntriesDeleted;

class DeleteShareableLinks
{
    /**
     * @var ShareableLink
     */
    private $link;

    /**
     * @param ShareableLink $link
     */
    public function __construct(ShareableLink $link)
    {
        $this->link = $link;
    }

    /**
     * @param  FileEntriesDeleted  $event
     * @return void
     */
    public function handle(FileEntriesDeleted $event)
    {
        if ($event->permanently) {
            $this->link->whereIn('entry_id', $event->entryIds)->delete();
        }
    }
}

<?php

namespace Common\Files\Events;

class FileEntriesDeleted
{
    /**
     * @var array
     */
    public $entryIds;

    /**
     * @var bool
     */
    public $permanently;

    /**
     * @param array $entryIds
     * @param boolean $permanently
     */
    public function __construct($entryIds, $permanently)
    {
        $this->entryIds = $entryIds;
        $this->permanently = $permanently;
    }
}

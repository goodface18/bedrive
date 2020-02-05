<?php

namespace App\Services\Links;

use Auth;
use App\FileEntry;
use App\ShareableLink;
use Illuminate\Support\Arr;
use Common\Database\Paginator;

class GetShareableLink
{
    /**
     * @var ShareableLink
     */
    private $link;

    /**
     * @var FileEntry
     */
    private $entry;

    /**
     * @param ShareableLink $link
     * @param FileEntry $entry
     */
    public function __construct(ShareableLink $link, FileEntry $entry)
    {
        $this->link = $link;
        $this->entry = $entry;
    }

    /**
     * Get shareable link by entry id or link hash.
     *
     * @param int|string $idOrHash
     * @param array $params
     * @return array
     */
    public function execute($idOrHash, $params = [])
    {
        if (is_integer($idOrHash) || ctype_digit($idOrHash)) {
            $response['link'] = $this->getByEntryId($idOrHash);
        } else {
            $parts = explode(':', $idOrHash);
            $response['link'] = $this->getByHash($parts[0], Arr::get($parts, 1));
        }

        if (Arr::get($params, 'withEntries')) {
            $response['link']->entry->load('users');
            $response['folderChildren'] = $this->getChildrenFor($response['link'], $params);
        }

        return $response;
    }

    private function getChildrenFor(ShareableLink $link, array $params)
    {
        $paginator = new Paginator($this->entry);
        $paginator->setDefaultOrderColumns('updated_at', 'desc');
        $params['perPage'] = 50;

        return $paginator
            ->with('users')
            ->where('parent_id', $link->entry->id)
            ->paginate($params);
    }

    private function getByHash($hash, $folderHash)
    {
        $link = $this->link
            ->where('hash', $hash)
            ->first();

        // load sub folder for main link entry, if folderHash provided
        if ($folderHash) {
            $link->setRelation('entry', $this->entry->whereHash($folderHash)->first());
        }

        return $link;
    }

    private function getByEntryId($entryId)
    {
        $userId = Auth::user()->id;

        return $this->link
            ->where('user_id', $userId)
            ->where('entry_id', $entryId)
            ->first();
    }
}
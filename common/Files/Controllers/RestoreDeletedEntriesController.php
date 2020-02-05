<?php

namespace Common\Files\Controllers;

use Common\Files\Actions\Deletion\RestoreEntries;
use Common\Files\FileEntry;
use Illuminate\Http\Request;
use Common\Core\Controller;

class RestoreDeletedEntriesController extends Controller
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Restore specified soft delete entries.
     *
     * @param RestoreEntries $action
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function restore(RestoreEntries $action)
    {
        $this->validate($this->request, [
            'entryIds' => 'required|array|exists:file_entries,id',
        ]);

        $entryIds = $this->request->get('entryIds');

        $this->authorize('destroy', [FileEntry::class, $entryIds]);

        $action->execute($entryIds);

        return $this->success();
    }
}
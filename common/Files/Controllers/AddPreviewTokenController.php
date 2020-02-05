<?php

namespace Common\Files\Controllers;

use Common\Core\Controller;
use Common\Files\FileEntry;
use Illuminate\Http\Request;

class AddPreviewTokenController extends Controller
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var FileEntry
     */
    private $fileEntry;

    /**
     * @param Request $request
     * @param FileEntry $fileEntry
     */
    public function __construct(Request $request, FileEntry $fileEntry)
    {
        $this->request = $request;
        $this->fileEntry = $fileEntry;
    }

    public function store($id)
    {
        $entry = $this->fileEntry->findOrFail($id);

        $this->authorize('show', $entry);

        $token = str_random(15);
        $entry->update(['preview_token' => $token]);

        return $this->success(['preview_token' => $token]);
    }
}
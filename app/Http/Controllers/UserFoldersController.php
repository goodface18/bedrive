<?php

namespace App\Http\Controllers;

use App\Folder;
use App\FileEntry;
use Common\Core\Controller;

class UserFoldersController extends Controller
{
    /**
     * @var Folder
     */
    private $folder;

    /**
     * @param Folder $folder
     */
    public function __construct(Folder $folder)
    {
        $this->folder = $folder;
    }

    /**
     * Display a listing of the resource.
     *
     * @param $userId
     * @return array
     */
    public function index($userId)
    {
        $this->authorize('index', [FileEntry::class, null, $userId]);

        $folders = $this->folder
            ->whereOwner($userId)
            ->select('file_entries.id', 'name', 'parent_id', 'path', 'type')
            ->orderByRaw('LENGTH(path)')
            ->limit(100)
            ->get();

        return $this->success(['folders' => $folders]);
    }
}

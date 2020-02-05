<?php

namespace Common\Files\Controllers;

use Illuminate\Http\Request;
use Common\Core\Controller;

class ServerMaxUploadSizeController extends Controller
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        if ( ! $this->request->user()->hasPermission('admin')) {
            abort(403);
        }

        return $this->success([
            'maxSize' => max(ini_get('post_max_size'), ini_get('upload_max_filesize'))
        ]);
    }
}
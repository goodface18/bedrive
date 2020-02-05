<?php

namespace App\Http\Controllers;

use Hash;
use App\ShareableLink;
use Illuminate\Http\Request;
use Common\Core\Controller;

class ShareableLinkPasswordController extends Controller
{
    /**
     * @var ShareableLink
     */
    private $link;

    /**
     * @var Request
     */
    private $request;

    /**
     * @param ShareableLink $link
     * @param Request $request
     */
    public function __construct(ShareableLink $link, Request $request)
    {
        $this->link = $link;
        $this->request = $request;
    }

    /**
     * Check whether link password matches.
     *
     * @param int $linkId
     * @return \Illuminate\Http\JsonResponse
     */
    public function check($linkId)
    {
        $link = $this->link->findOrFail($linkId);
        $password = $this->request->get('password');

        return $this->success([
            'matches' => Hash::check($password, $link->password)
        ]);
    }
}

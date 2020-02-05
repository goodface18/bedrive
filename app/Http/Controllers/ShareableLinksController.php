<?php

namespace App\Http\Controllers;

use App\FileEntry;
use App\Http\Requests\CrupdateShareableLinkRequest;
use App\Services\Links\CrupdateShareableLink;
use App\Services\Links\GetShareableLink;
use App\ShareableLink;
use Illuminate\Http\Request;
use Common\Core\Controller;

class ShareableLinksController extends Controller
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var ShareableLink
     */
    private $link;

    /**
     * @var FileEntry
     */
    private $entry;

    /**
     * @param Request $request
     * @param ShareableLink $link
     * @param FileEntry $entry
     */
    public function __construct(Request $request, ShareableLink $link, FileEntry $entry)
    {
        $this->request = $request;
        $this->link = $link;
        $this->entry = $entry;
    }

    /**
     * @param int|string $idOrHash
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($idOrHash)
    {
        $response = app(GetShareableLink::class)
            ->execute($idOrHash, $this->request->all());

        $link = $response['link'];
        if ( ! $link || ! $link->entry || $link->entry->trashed()) abort(404);

        $this->authorize('show', $link);

        return $this->success($response);
    }

    /**
     * @param int $entryId
     * @param CrupdateShareableLinkRequest $request
     * @param CrupdateShareableLink $action
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store($entryId, CrupdateShareableLinkRequest $request, CrupdateShareableLink $action)
    {
        $this->authorize('create', ShareableLink::class);
        $this->authorize('update', [FileEntry::class, [$entryId]]);

        $params = $request->all();
        $params['userId'] = $request->user()->id;
        $params['entryId'] = $entryId;

        $link = $action->execute($params);

        return $this->success(['link' => $link]);
    }

    /**
     * @param int $id
     * @param CrupdateShareableLinkRequest $request
     * @param CrupdateShareableLink $action
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update($id, CrupdateShareableLinkRequest $request, CrupdateShareableLink $action)
    {
        $link = $this->link->findOrFail($id);

        $this->authorize('update', $link);

        $params = $request->all();
        $params['userId'] = $request->user()->id;

        $action->execute($params, $link);

        return $this->success(['link' => $link]);
    }

    /**
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy($id)
    {
        /**
         * @var ShareableLink $link
         */
        $link = $this->link->findOrFail($id);

        $this->authorize('destroy', $link);

        $link->delete();

        return $this->success();
    }
}

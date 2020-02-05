<?php

namespace App\Http\Controllers;

use App\FileEntry;
use Illuminate\Http\Request;
use Common\Core\Controller;
use Common\Tags\Tag;

class StarredEntriesController extends Controller
{
    const TAG_NAME = 'starred';

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Tag
     */
    private $tag;

    /**
     * @param Request $request
     * @param Tag $tag
     */
    public function __construct(Request $request, Tag $tag)
    {
        $this->request = $request;
        $this->tag = $tag;
    }

    /**
     * Attach "starred" tag to specified entries.
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function add()
    {
        $entryIds = $this->request->get('ids');

        $this->validate($this->request, [
            'ids' => 'required|array|exists:file_entries,id'
        ]);

        $this->authorize('update', [FileEntry::class, $entryIds]);

        $tag = $this->tag->where('name', self::TAG_NAME)->first();

        $tag->attachEntries($entryIds, $this->request->user()->id);

        return $this->success(['tag' => $tag]);
    }

    /**
     * Detach "starred" tag from specified entries.
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function remove()
    {
        $entryIds = $this->request->get('ids');

        $this->validate($this->request, [
            'ids' => 'required|array|exists:file_entries,id'
        ]);

        $this->authorize('update', [FileEntry::class, $entryIds]);

        $tag = $this->tag->where('name', self::TAG_NAME)->first();

        $tag->detachEntries($entryIds, $this->request->user()->id);

        return $this->success(['tag' => $tag]);
    }
}

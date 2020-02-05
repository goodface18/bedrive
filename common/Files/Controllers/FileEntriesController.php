<?php namespace Common\Files\Controllers;

use App;
use Auth;
use Common\Database\Paginator;
use Common\Files\Actions\CreateFileEntry;
use Common\Files\Actions\Storage\StorePrivateUpload;
use Common\Files\Events\FileEntriesDeleted;
use Common\Files\Events\FileEntryCreated;
use Common\Files\Requests\UploadFile;
use Common\Files\FileEntry;
use Illuminate\Http\Request;
use Common\Core\Controller;
use Common\Files\Actions\Deletion\SoftDeleteEntries;
use Common\Files\Response\FileContentResponseCreator;
use Common\Files\Actions\Deletion\PermanentlyDeleteEntries;

class FileEntriesController extends Controller {

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var FileEntry
     */
    protected $entry;

    /**
     * @param Request $request
     * @param FileEntry $entry
     */
    public function __construct(Request $request, FileEntry $entry)
    {
        $this->request = $request;
        $this->entry = $entry;
    }

    /**
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function index()
    {
        $params = $this->request->all();
        $params['userId'] = $this->request->get('userId', Auth::id());

        $this->authorize('index', FileEntry::class);

        return (new Paginator($this->entry))
            ->with('users')
            ->paginate($params);
    }

    /**
     * @param int $id
     * @param FileContentResponseCreator $response
     * @return mixed
     */
    public function show($id, FileContentResponseCreator $response)
    {
        if ((int) $id === 0) {
            $id = $this->entry->decodeHash($id);
        }

        $entry = $this->entry->withTrashed()->findOrFail($id);

        $this->authorize('show', $entry);

        return $response->create($entry);
    }

    /**
     * @param UploadFile $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(UploadFile $request)
    {
        $path = $this->request->get('path');
        $parentId = $request->get('parentId');
        $uploadedFile = $this->request->file('file');

        $this->authorize('store', [FileEntry::class, $parentId]);
        
        $fileEntry = app(CreateFileEntry::class)->execute(
            $uploadedFile,
            ['parent_id' => $parentId, 'path' => $path]
        );

        app(StorePrivateUpload::class)->execute($fileEntry, $uploadedFile);

        event(new FileEntryCreated($fileEntry, $this->request->except('file')));

        return $this->success(['fileEntry' => $fileEntry->load('users')], 201);
    }

    /**
     * @param int $entryId
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update($entryId)
    {
        $this->authorize('update', [FileEntry::class, [$entryId]]);

        $this->validate($this->request, [
            'name' => 'string|min:3|max:200',
            'description' => 'nullable|string|min:3|max:200',
        ]);

        $entry = $this->entry->findOrFail($entryId);

        $entry->fill($this->request->all())->update();

        return $this->success(['fileEntry' => $entry]);
    }

    /**
     * Delete specified file entries from disk and database.
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy()
    {
        $entryIds = $this->request->get('entryIds');
        $userId = Auth::user()->id;

        $this->validate($this->request, [
            'entryIds' => 'requiredWithout:emptyTrash|array|exists:file_entries,id',
            'deleteForever' => 'boolean',
            'emptyTrash' => 'boolean'
        ]);

        //get all soft deleted entries for user, if we are emptying trash
        if ($this->request->get('emptyTrash')) {
            $entryIds = $this->entry
                ->whereOwner($userId)
                ->onlyTrashed()
                ->pluck('id')
                ->toArray();
        }

        $this->authorize('destroy', [FileEntry::class, $entryIds]);

        $permanent = $this->request->get('deleteForever') || $this->request->get('emptyTrash');

        if ($permanent) {
            App::make(PermanentlyDeleteEntries::class)->execute($entryIds);
        } else {
            App::make(SoftDeleteEntries::class)->execute($entryIds);
        }

        event(new FileEntriesDeleted($entryIds, $permanent));

        return $this->success();
    }
}

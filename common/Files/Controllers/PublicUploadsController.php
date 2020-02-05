<?php namespace Common\Files\Controllers;

use Common\Files\Actions\CreateFileEntry;
use Common\Files\Actions\Storage\StorePublicUpload;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Common\Core\Controller;
use Common\Files\FileEntry;

class PublicUploadsController extends Controller {

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
     * Store video or music files without attaching them to any database records.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function videos()
    {
        $this->authorize('store', FileEntry::class);

        $this->validate($this->request, [
            'type'    => 'required|string|in:track',
            'file' => 'required|file'
        ]);

        $fileEntry = $this->storePublicFile();

        return response(['fileEntry' => $fileEntry], 201);
    }

    /**
     * Store images on public disk.
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function images() {

        $this->authorize('store', FileEntry::class);

        $this->validate($this->request, [
            'type'    => 'required_without:path|string|min:1',
            'path'    => 'required_without:type|string|min:1',
            'file' => 'required|file|image'
        ]);

        $fileEntry = $this->storePublicFile();

        return response(['fileEntry' => $fileEntry], 201);
    }

    /**
     * @return FileEntry
     */
    private function storePublicFile()
    {
        $type = $this->request->get('type');
        $uploadFile = $this->request->file('file');
        $publicPath = $this->request->has('path') ? $this->request->get('path') : "{$type}_media";

        $fileEntry = app(CreateFileEntry::class)->execute($uploadFile, ['public_path' => $publicPath]);

        app(StorePublicUpload::class)->execute($fileEntry, $uploadFile);

        return $fileEntry;
    }
}

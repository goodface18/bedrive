<?php namespace Common\Files\Response;

use Storage;
use Response;
use Common\Files\FileEntry;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileContentResponseCreator {

    /**
     * @var ImageResponse
     */
    private $imageResponse;

    /**
     * @var AudioVideoResponse
     */
    private $audioVideoResponse;

    /**
     * @param ImageResponse $imageResponse
     * @param AudioVideoResponse $audioVideoResponse
     */
    public function __construct(ImageResponse $imageResponse, AudioVideoResponse $audioVideoResponse)
    {
        $this->imageResponse = $imageResponse;
        $this->audioVideoResponse = $audioVideoResponse;
    }

    /**
     * Return download or preview response for given file.
     *
     * @param FileEntry  $upload
     *
     * @return mixed
     */
    public function create(FileEntry $upload)
    {
        if ( ! $upload->getDisk()->exists($upload->getStoragePath())) abort(404);

        list($mime, $type) = $this->getTypeFromModel($upload);

        if ($type === 'image') {
            return $this->imageResponse->create($upload);
        } elseif ($this->shouldStream($mime, $type)) {
            return $this->audioVideoResponse->create($upload);
        } else {
            return $this->createBasicResponse($upload);
        }
    }

    /**
     * Create a basic response for specified upload content.
     *
     * @param FileEntry $upload
     * @return StreamedResponse
     */
    private function createBasicResponse(FileEntry $upload)
    {
        $fs = $upload->getDisk();
        $stream = $fs->readStream($upload->getStoragePath());

        return Response::stream(function() use($stream) {
            fpassthru($stream);
        }, 200, [
            "Content-Type" => $fs->getMimetype($upload->getStoragePath()),
            "Content-Length" => $fs->getSize($upload->getStoragePath()),
            "Content-disposition" => "inline; filename=\"" . $upload->name . "\"",
        ]);
    }

    /**
     * Extract file type from model.
     *
     * @param FileEntry $fileModel
     * @return array
     */
    private function getTypeFromModel(FileEntry $fileModel)
    {
        $mime = $fileModel->mime;
        $type = explode('/', $mime)[0];

        return array($mime, $type);
    }

    /**
     * Should file with given mime be streamed.
     *
     * @param string $mime
     * @param string $type
     *
     * @return bool
     */
    private function shouldStream($mime, $type) {
        return $type === 'video' || $type === 'audio' || $mime === 'application/ogg';
    }
}
<?php namespace Common\Files\Response;

use Request;
use Common\Files\FileEntry;
use Symfony\Component\HttpFoundation\Response;

class ImageResponse {

    /**
     * Create response for previewing specified image.
     * Optionally resize image to specified size.
     *
     * @param FileEntry $entry
     * @return Response
     */
    public function create(FileEntry $entry)
    {
        $path = Request::get('thumbnail') && $entry->thumbnail ?
            "{$entry->file_name}/thumbnail.jpg" :
            $entry->getStoragePath();

        $content = $entry->getDisk()->get($path);
        return response($content, 200, ['Content-Type' => $entry->mime]);
    }
}
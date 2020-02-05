<?php

namespace Common\Files\Actions;

use Common\Files\FileEntry;
use Common\Files\Traits\GetsEntryTypeFromMime;
use Illuminate\Http\UploadedFile;

class UploadedFileToArray
{
    use GetsEntryTypeFromMime;

    /**
     * @var FileEntry
     */
    private $entry;

    /**
     * @param FileEntry $entry
     */
    public function __construct(FileEntry $entry)
    {
        $this->entry = $entry;
    }

    /**
     * @param UploadedFile $file
     * @return array
     */
    public function execute(UploadedFile $file)
    {
        // TODO: move mime/extension/type guessing into separate class
        $originalMime = $file->getMimeType();

        if ($originalMime === 'application/octet-stream') {
            $originalMime = $file->getClientMimeType();
        }

        $data = [
            'name' => $file->getClientOriginalName(),
            'file_name' => str_random(40),
            'mime' => $originalMime,
            'type' => $this->getTypeFromMime($originalMime),
            'file_size' => $file->getClientSize(),
            'extension' => $this->getExtension($file, $originalMime),
        ];

        return $data;
    }

    /**
     * Extract file extension from specified file data.
     *
     * @param UploadedFile $file
     * @param string $mime
     * @return string
     */
    private function getExtension(UploadedFile $file, $mime)
    {
        if ($extension = $file->getClientOriginalExtension()) {
            return $extension;
        }

        $pathinfo = pathinfo($file->getClientOriginalName());

        if (isset($pathinfo['extension'])) return $pathinfo['extension'];

        return explode('/', $mime)[1];
    }
}
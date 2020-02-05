<?php

namespace Common\Files\Actions\Storage;

use Storage;
use Common\Files\FileEntry;
use Illuminate\Http\UploadedFile;

class StorePublicUpload
{
    /**
     * @param FileEntry $entry
     * @param UploadedFile|string $contents
     */
    public function execute(FileEntry $entry, $contents)
    {
        if (is_a($contents, UploadedFile::class)) {
            Storage::disk('public')->putFileAs($entry->public_path, $contents, $entry->file_name);
        } else {
            Storage::disk('public')->put("{$entry->public_path}/{$entry->file_name}", $contents);
        }
    }
}
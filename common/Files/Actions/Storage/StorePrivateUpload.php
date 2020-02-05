<?php

namespace Common\Files\Actions\Storage;

use Image;
use Storage;
use Common\Files\FileEntry;
use Illuminate\Http\UploadedFile;
use Intervention\Image\Constraint;
use Illuminate\Filesystem\FilesystemAdapter;

class StorePrivateUpload
{
    /**
     * @param FileEntry $entry
     * @param UploadedFile|string $contents
     */
    public function execute(FileEntry $entry, $contents)
    {
        $disk = Storage::disk(config('common.site.uploads_disk'));

        if (is_a($contents, UploadedFile::class)) {
            $disk->putFileAs($entry->file_name, $contents, $entry->file_name);
        } else {
            $disk->put("{$entry->file_name}/{$entry->file_name}", $contents);
        }

        $this->maybeCreateThumbnail($disk, $entry, $contents);
    }

    private function maybeCreateThumbnail(FilesystemAdapter $disk, FileEntry $entry, $contents)
    {
        // only create thumbnail for images over 500KB in size
        if ($entry->type === 'image' && $entry->file_size > 500000) {
            $img = Image::make($contents);

            $img->fit(350, 250, function (Constraint $constraint) {
                $constraint->upsize();
            });

            $img->encode('jpg', 60);

            $disk->put("{$entry->file_name}/thumbnail.jpg", $img);

            $entry->fill(['thumbnail' => true])->save();
        }
    }
}
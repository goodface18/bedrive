<?php namespace Common\Files\Controllers;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Common\Core\Controller;
use Common\Files\FileEntry;
use ZipArchive;

class DownloadFileController extends Controller
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var FileEntry
     */
    private $fileEntry;

    /**
     * @param Request $request
     * @param FileEntry $fileEntry
     */
    public function __construct(Request $request, FileEntry $fileEntry)
    {
        $this->request = $request;
        $this->fileEntry = $fileEntry;
    }

    public function download()
    {
        // TODO: limit to N records or chunk

        $hashes = explode(',', $this->request->get('hashes'));
        $ids = array_map(function($hash) {
            return $this->fileEntry->decodeHash($hash);
        }, $hashes);

        $entries = $this->fileEntry->whereIn('id', $ids)->get();

        // TODO: refactor file entry policy to accent multiple IDs
        $entries->each(function($entry) {
            $this->authorize('show', [FileEntry::class, $entry]);
        });

        if ($entries->count() === 1 && $entries->first()->type !== 'folder') {
            $entry = $entries->first();

            $disk = $entry->getDisk();
            $stream = $disk->readStream($entry->getStoragePath());

            return response()->stream(function() use($stream) {
                fpassthru($stream);
            }, 200, [
                "Content-Type" => $entry->mime,
                "Content-Length" => $disk->size($entry->getStoragePath()),
                "Content-disposition" => "attachment; filename=\"" . $entry->name . "\"",
            ]);
        } else {
            $path = $this->createZip($entries);
            $timestamp = Carbon::now()->getTimestamp();
            return response()->download($path, "download-$timestamp.zip");
        }
    }

    /**
     * Create a zip archive for download.
     *
     * @param Collection $entries
     * @return string
     */
    private function createZip(Collection $entries) {
        $random = str_random();
        $path = storage_path("app/temp/zips/$random.zip");
        $zip = new ZipArchive();

        $zip->open($path, ZIPARCHIVE::CREATE);

        $this->fillZip($zip, $entries);

        $zip->close();

        return $path;
    }

    /**
     * @param ZipArchive $zip
     * @param Collection $entries
     */
    private function fillZip(ZipArchive $zip, Collection $entries) {
        $entries->each(function(FileEntry $entry) use($zip) {
            if ($entry->type === 'folder') {
                $zip->addEmptyDir($entry->getNameWithExtension());
                $children = $entry->findChildren();
                $children->each(function(FileEntry $childEntry) use($zip, $entry, $children) {
                    $path = $this->transformPath($childEntry, $entry, $children);
                    if ($childEntry->type === 'folder') {
                        $zip->addEmptyDir($path);
                    } else {
                        $zip->addFromString($path, $this->getFileContents($childEntry));
                    }
                });
            } else {
                $zip->addFromString($entry->getNameWithExtension(), $this->getFileContents($entry));
            }
        });
    }

    /**
     * Replace entry IDs with names inside "path" property.
     *
     * @param FileEntry $entry
     * @param FileEntry $parent
     * @param Collection $folders
     * @return string
     */
    private function transformPath(FileEntry $entry, FileEntry $parent, Collection $folders)
    {
        if ( ! $entry->path) return $entry->getNameWithExtension();

        // '56/55/54 => [56,55,54]
        $path = array_filter(explode('/', $entry->path));
        $path = array_map(function($id) {
            return (int) $id;
        }, $path);

        //only generate path until specified parent and not root
        $path = array_slice($path, array_search($parent->id, $path));

        // last value will be id of the file itself, remove it
        array_pop($path);

        //map parent folder IDs to names
        $path = array_map(function($id) use($folders) {
            return $folders->find($id)->name;
        }, $path);

        return implode('/', $path) . '/' . $entry->getNameWithExtension();
    }

    private function getFileContents(FileEntry $entry) {
        return $entry->getDisk()->get($entry->getStoragePath());
    }
}

<?php

namespace App\Listeners;

use App\Services\Shares\AttachUsersToEntry;
use Common\Files\Actions\CreateFileEntry;
use Common\Files\Actions\Storage\StorePrivateUpload;
use Common\Auth\Events\UserCreated;
use Illuminate\Filesystem\Filesystem;
use App\Services\Entries\CreateFolder;
use Illuminate\Http\UploadedFile;

class HydrateUserWithSampleDriveContents
{
    /**
     * @var string
     */
    private $samplesPath;

    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @param Filesystem $fs
     */
    public function __construct(Filesystem $fs)
    {
        $this->fs = $fs;
        $this->samplesPath = base_path('../sample-files/');
    }

    /**
     * Handle the event.
     *
     * @param  UserCreated $event
     * @return void
     */
    public function handle(UserCreated $event)
    {
        $user = $event->user;

        $this->hydrateFolder('root', $user->id);

        $images = $this->hydrateFolder('images', $user->id);
        $this->hydrateFolder('nested folder', $user->id, $images->id);

        $this->hydrateFolder('documents', $user->id);

        $folder = $this->hydrateFolder('shared', $user->id);
        app(AttachUsersToEntry::class)->execute(['tester@tester.com'], [$folder], ['view' => true]);
    }

    private function hydrateFolder($name, $userId, $parentId = null)
    {
        if ($name !== 'root') {
            $folder = app()->make(CreateFolder::class)->execute(['name' => ucwords($name), 'userId' => $userId, 'parentId' => $parentId]);
        }

        $this->createFiles($name, isset($folder) ? $folder->id : null, $userId);

        return isset($folder) ? $folder : null;
    }

    private function createFiles($dirName, $parentId, $userId)
    {
        $folderPath = $this->samplesPath . $dirName;

        if ( ! $this->fs->exists($folderPath)) return;

        foreach ($this->fs->files($folderPath) as $path) {
            $uploadedFile = new UploadedFile(
                $path,
                basename($path),
                $this->fs->mimeType($path),
                $this->fs->size($path)
            );

            $fileEntry = app(CreateFileEntry::class)->execute($uploadedFile, ['parent_id' => $parentId, 'user_id' => $userId]);

            app(StorePrivateUpload::class)->execute($fileEntry, $uploadedFile);
        }
    }
}

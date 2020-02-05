<?php

use Illuminate\Database\Migrations\Migration;

class UpdateUploadsStructureToV2 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $disk = config('common.site.uploads_disk');
        $current = Storage::drive($disk);

        if ($disk == 'uploads_local') {
            $legacy = Storage::drive('legacy_local');
        } else {
            $legacy = Storage::drive($disk);
        }

        // loop directories named by user id
        foreach ($legacy->directories('application/storage/uploads') as $userFolderPath) {
            $folderBaseName = pathinfo($userFolderPath)['basename'];

            //loop directories inside user folder named by file id
            if ((int) $folderBaseName > 0) {
                foreach($legacy->directories($userFolderPath) as $fileFolderPath) {
                    $filePath = $legacy->files($fileFolderPath)[0];
                    $hash = pathinfo($filePath)['filename'];
                    if ( ! $current->exists($hash)) {
                        if ($current->put("$hash/$hash", $legacy->get($filePath))) {
                            $legacy->deleteDir($fileFolderPath);
                        }
                    } else {
                        $legacy->deleteDir($fileFolderPath);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}

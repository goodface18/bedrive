<?php

use Illuminate\Support\Collection;
use Common\Files\Traits\HandlesEntryPaths;
use Illuminate\Database\Migrations\Migration;

class MoveFoldersIntoFileEntriesTable extends Migration
{
    use HandlesEntryPaths;

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('folders')->orderBy('id')->chunk(50, function(Collection $folders) {
            $records = $folders
                ->filter(function($folder) {
                    return $folder->name !== 'root';
                })->map(function($folder) {
                    return [
                        'name' => $folder->name,
                        'file_name' => str_random(40),
                        'path' => $folder->path,
                        'description' => $folder->description,
                        'mime' => null,
                        'file_size' => null,
                        'password' => $folder->password,
                        'created_at' => $folder->created_at,
                        'updated_at' => $folder->updated_at,
                        'deleted_at' => $folder->deleted_at,
                        'user_id' => $folder->user_id,
                        'type' => 'folder',
                        'extension' => null,
                        'public' => 0,
                        'public_path' => null,
                    ];
                });

            DB::table('file_entries')->insert($records->toArray());
        });

        $this->generateFolderPaths();
    }

    private function generateFolderPaths()
    {
        DB::table('file_entries')
            ->where('type', 'folder')
            ->orderBy('id', 'desc')
            ->chunk(50, function(Collection $folders) {
                $names = $folders
                    ->pluck('path')
                    ->map(function($path) {
                        return explode('/', $path);
                    })->flatten()->unique();

                // fetch all folders needed to convert folder paths from names to ids
                $pathFolders = DB::table('file_entries')
                    ->whereIn('user_id', $folders->pluck('user_id'))
                    ->whereIn('name', $names)
                    ->get();

                $folders->each(function($folder) use($pathFolders) {
                    // "root" prefix no longer needed in latest version
                    $path = str_replace('root/', '', $folder->path);
                    $userId = $folder->user_id;

                    // map folder names to ids "parent/child/folder" => "78/54/96"
                    $pathIds = array_map(function($folderName) use($pathFolders, $userId) {
                        $pathFolder = $pathFolders->first(function($folder) use($userId, $folderName) {
                            return $folder->name === $folderName && $folder->user_id === $userId;
                        });
                        return $pathFolder ? $pathFolder->id : null;
                    }, explode('/', $path));

                    // encode ids to base36
                    $encodedPathIds = array_map(function($id) {
                        return base_convert($id, 10, 36);
                    }, $pathIds);

                    $pathCount = count($pathIds);

                    // update folder path and parent id
                    DB::table('file_entries')
                        ->where('id', $folder->id)
                        ->update([
                            'path' => implode('/', $encodedPathIds),
                            'parent_id' => $pathCount > 1 ? $pathIds[$pathCount - 2] : null
                        ]);

                    // update parent_id of all folder children
                    $oldId = DB::table('folders')->where('name', $folder->name)->where('user_id', $folder->user_id)->first()->id;
                    DB::table('file_entries')
                        ->where('parent_id', $oldId)
                        ->update(['parent_id' => $folder->id]);

                });
            });
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

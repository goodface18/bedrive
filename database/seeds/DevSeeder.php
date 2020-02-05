<?php

use App\User;
use App\Folder;
use App\File;
use Illuminate\Database\Seeder;

class DevSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //DB::table('file_entries')->truncate();
        //DB::table('user_file_entry')->truncate();

        $user = User::find(1);
        //$user = factory(User::class)->create();
        $this->createChildFolders($user);

        $this->createEntries(File::class, 100000, $user);
    }

    /**
     * @param string $type
     * @param int $amount
     * @param User|null $user
     */
    private function createEntries($type, $amount = 100, User $user = null)
    {
        $chunks = collect(array_fill(0, $amount, 0))->chunk(200);

        $chunks->each(function() use($user, $type) {
            $files = factory($type, 200)->create();

            if ($user) {
                $pivot = $files->pluck('id')->combine(array_fill(0, $files->count(), ['owner' => true]));
                $user->files()->syncWithoutDetaching($pivot);
            }
        });
    }

    private function createChildFolders(User $user) {
        $user->folders()->limit(100)->chunk(200, function($chunk) {
            $chunk->each(function($folder) {
                $child = factory(Folder::class)->create(['parent_id' => $folder]);
                $child->generatePath();

                $depth = array_fill(0, 50, 0);
                $lastChild = $child;
                foreach ($depth as $value) {
                    $innerChild = factory(Folder::class)->create(['parent_id' => $lastChild]);
                    $innerChild->generatePath();
                }
            });
        });
    }
}

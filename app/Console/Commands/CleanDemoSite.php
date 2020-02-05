<?php

namespace App\Console\Commands;

use App\Listeners\HydrateUserWithSampleDriveContents;
use Artisan;
use Common\Auth\Events\UserCreated;
use DB;
use Hash;
use Storage;
use App\User;
use Carbon\Carbon;
use Common\Files\FileEntry;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Common\Localizations\Localization;

class CleanDemoSite extends Command
{
    /**
     * @var string
     */
    protected $signature = 'demoSite:clean';

    /**
     * @var FileEntry
     */
    private $entry;

    /**
     * @var User
     */
    private $user;

    /**
     * @var Localization
     */
    private $localization;

    /**
     * @param FileEntry $entry
     * @param User $user
     * @param Localization $localization
     */
    public function __construct(FileEntry $entry, User $user, Localization $localization)
    {
        parent::__construct();
        $this->entry = $entry;
        $this->user = $user;
        $this->localization = $localization;
    }

    /**
     * @return void
     */
    public function handle()
    {
        $entries = $this->entry
            ->whereDate('created_at', '<', Carbon::now()->subDay())
            ->get();

        $this->cleanAdminUser('admin@admin.com');
        $this->cleanEntries($entries);
        $this->rehydrateDemoAccounts();

        Artisan::call('cache:clear');

        $this->info('Demo site cleaned successfully');
    }

    private function rehydrateDemoAccounts()
    {
        $users = $this->user->where('email', 'like', 'admin@demo%')->get();

        $users->each(function($user) {
            $this->cleanAdminUser($user->email);
        });
    }

    /**
     * Clean specified entries and their children.
     *
     * @param Collection $entries
     */
    private function cleanEntries($entries)
    {
        $entries->each(function(FileEntry $entry) {
            $parentAndChildren = $entry->findChildren();
            $parentAndChildren->push($entry);
            $this->removeEntries($parentAndChildren);
        });
    }

    /**
     * @param Collection $entries
     */
    private function removeEntries($entries)
    {
        $ids = $entries->pluck('id');

        // detach from users
        DB::table('user_file_entry')
            ->whereIn('file_entry_id', $ids)
            ->delete();

        // detach tags
        DB::table('taggables')
            ->whereIn('taggable_id', $ids)
            ->where('taggable_type', FileEntry::class)
            ->delete();

        // delete shareable links
        DB::table('shareable_links')
            ->whereIn('entry_id', $ids)
            ->delete();

        $paths = $entries->filter(function(FileEntry $entry) {
            return $entry->type !== 'folder';
        })->map(function(FileEntry $entry) {
            return $entry->file_name;
        });

        // delete files from disk
        foreach ($paths as $path) {
            Storage::disk(config('common.site.uploads_disk'))->deleteDir($path);
        }

        // delete entries
        DB::table('file_entries')->whereIn('id', $ids)->delete();
    }

    private function cleanAdminUser($email)
    {
        $admin = $this->user
            ->with('entries')
            ->where('email', $email)
            ->first();

        if ( ! $admin) return;

        $admin->avatar = null;
        $admin->username = 'admin';
        $admin->first_name = 'Demo';
        $admin->last_name = 'Admin';
        $admin->password = Hash::make('admin');
        $admin->permissions = ['admin' => 1, 'superAdmin' => 1];
        $admin->save();

        // delete file entries
        $this->cleanEntries($admin->entries);

        // delete localizations
        $this->localization->get()->each(function(Localization $localization) {
            if (strtolower($localization->name) !== 'english') {
                $localization->delete();
            }
        });

        // rehydrate
        app(HydrateUserWithSampleDriveContents::class)
            ->handle(new UserCreated($admin));
    }
}

<?php

namespace App\Providers;

use App\File;
use App\Folder;
use App\Policies\DriveFileEntryPolicy;
use App\Policies\ShareableLinkPolicy;
use App\ShareableLink;
use Common\Files\FileEntry;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Model' => 'App\Policies\ModelPolicy',
        File::class => DriveFileEntryPolicy::class,
        Folder::class => DriveFileEntryPolicy::class,
        FileEntry::class => DriveFileEntryPolicy::class,
        ShareableLink::class => ShareableLinkPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        //
    }
}

<?php

namespace App\Providers;

use App\Listeners\DeleteShareableLinks;
use Common\Files\Events\FileEntriesDeleted;
use Event;
use Common\Auth\Events\UserCreated;
use Common\Files\Events\FileEntryCreated;
use App\Listeners\AttachUsersToNewlyUploadedFile;
use App\Listeners\HydrateUserWithSampleDriveContents;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        FileEntryCreated::class => [
            AttachUsersToNewlyUploadedFile::class,
        ],

        FileEntriesDeleted::class => [
            DeleteShareableLinks::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        if (config('common.site.demo')) {
            Event::listen(UserCreated::class, HydrateUserWithSampleDriveContents::class);
        }
    }
}

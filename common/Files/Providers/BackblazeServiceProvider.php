<?php

namespace Common\Files\Providers;

use Storage;
use League\Flysystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Mhetreramesh\Flysystem\BackblazeAdapter;
use BackblazeB2\Client as BackblazeClient;

class BackblazeServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        Storage::extend('backblaze', function ($app, $config) {
            $client = new BackblazeClient($config['account_id'], $config['application_key']);
            return new Filesystem(new BackblazeAdapter($client, $config['bucket']));
        });
    }

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
<?php

namespace Common\Files\Providers;

use Aws\S3\S3Client;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use Storage;
use League\Flysystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class DigitalOceanServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        Storage::extend('digitalocean', function ($app, $config) {
            $region = $config['region'];

            $client = new S3Client([
                'credentials' => [
                    'key'    => $config['key'],
                    'secret' => $config['secret']
                ],
                'region' => $region,
                'version' => 'latest',
                'endpoint' => "https://$region.digitaloceanspaces.com",
            ]);

            $adapter = new AwsS3Adapter($client, $config['bucket']);

            return new Filesystem($adapter);
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
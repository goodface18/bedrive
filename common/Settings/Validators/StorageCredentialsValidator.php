<?php

namespace Common\Settings\Validators;

use Common\Files\Providers\BackblazeServiceProvider;
use Config;
use Storage;
use Exception;
use Common\Files\Providers\DropboxServiceProvider;
use Common\Files\Providers\DigitalOceanServiceProvider;

class StorageCredentialsValidator
{
    const KEYS = [
        'uploads_disk',

        // dropbox
        'uploads_dropbox_access_token', 'uploads_dropbox_root',

        // s3
        'uploads_s3_key', 'uploads_s3_secret',
        'uploads_s3_region', 'uploads_s3_bucket',

        // ftp
        'uploads_ftp_host', 'uploads_ftp_username', 'uploads_ftp_password',
        'uploads_ftp_root', 'uploads_ftp_port', 'uploads_ftp_passive', 'uploads_ftp_ssl',

        // digital ocean
        'uploads_digitalocean_key', 'uploads_digitalocean_secret',
        'uploads_digitalocean_region', 'uploads_digitalocean_bucket',

        // rackspace
        'uploads_rackspace_username', 'uploads_rackspace_key',
        'uploads_rackspace_region', 'uploads_rackspace_container',
    ];


    public function fails($settings)
    {
        $this->setConfigDynamically($settings);
        $this->registerAdapters();

        $driverName = $this->getDriverName();

        try {
            $disk = Storage::disk(config('common.site.uploads_disk'));
            if ($driverName === 'dropbox') {
                // dropbox adapter catches all errors silently
                // need to use client directly to check for errors
                $disk->getAdapter()->getClient()->getMetadata('foo-bar');
            } else {
                $disk->has('foo-bar');
            }
        } catch (Exception $e) {
            return ['storage_group' => "These $driverName credentials are not valid."];
        }
    }

    private function setConfigDynamically($settings)
    {
        $replacements = ['s3', 'dropbox', 'ftp', 'digitalocean', 'rackspace', 'backblaze'];

        foreach ($settings as $key => $value) {
            if ($key === 'uploads_disk') {
                Config::set('common.site.uploads_disk', $value ?: null);
            } else {
                // uploads_s3_key => filesystems.disks.uploads_s3.key
                $key = 'filesystems.disks.' . $key;
                foreach ($replacements as $replacement) {
                    $key = str_replace("{$replacement}_", "{$replacement}.", $key);
                }
                Config::set($key, $value ?: null);
            }
        }
    }

    private function getDriverName()
    {
        $diskName = config('common.site.uploads_disk');
        return config("filesystems.disks.$diskName.driver");
    }

    private function registerAdapters()
    {
        app()->register(DigitalOceanServiceProvider::class);
        app()->register(DropboxServiceProvider::class);
        app()->register(BackblazeServiceProvider::class);
    }
}
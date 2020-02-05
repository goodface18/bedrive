<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;

class Folder extends FileEntry
{
    protected $attributes = [
        'type' => 'folder'
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('fsType', function (Builder $builder) {
            $builder->where('type', 'folder');
        });
    }
}

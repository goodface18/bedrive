<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;

class File extends FileEntry
{
    protected $table = 'file_entries';

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('fsType', function (Builder $builder) {
            $builder->where('type', '!=', 'folder');
        });
    }
}

<?php

namespace Common\Files;

use Illuminate\Database\Eloquent\Relations\Pivot;

class UserFileEntry extends Pivot
{
    protected $table = 'user_file_entry';

    protected $casts = ['owner' => 'boolean'];

    /**
     * @param $value
     * @return array
     */
    public function getPermissionsAttribute($value)
    {
        if ( ! $value) return [];

        if (is_string($value)) {
            return json_decode($value, true);
        }

        return $value;
    }
}

<?php

namespace Common\Files\Traits;

trait SetsAvailableSpaceAttribute
{
    /**
     * Large numbers are not stored in db on some servers properly without this.
     *
     * @param int $value
     */
    public function setAvailableSpaceAttribute($value) {
        if ( ! config('database.mysql.strict') && ! is_null($value)) {
            $this->attributes['available_space'] = (string) $value;
        } else {
            $this->attributes['available_space'] = $value;
        }
    }
}
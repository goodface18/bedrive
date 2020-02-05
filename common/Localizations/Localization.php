<?php namespace Common\Localizations;

use Illuminate\Database\Eloquent\Model;

/**
 * Common\Localizations\Localization
 *
 * @property int $id
 * @property string $name
 * @property string $lines
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @mixin \Eloquent
 */
class Localization extends Model
{
    protected $guarded = ['id'];

    /**
     * Decode lines json attribute.
     *
     * @param string $text
     * @return array
     */
    public function getLinesAttribute($text) {
        if ( ! $text) return [];

        return json_decode($text, true);
    }

    public function setNameAttribute($name)
    {
        $this->attributes['name'] = str_slug($name);
    }
}

<?php namespace Common\Settings;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $value
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $private
 * @mixin \Eloquent
 */
class Setting extends Model {

	/**
	 * @var string
	 */
	protected $table = 'settings';

    protected $fillable = ['name', 'value'];

    protected $casts = ['private' => 'integer'];

    /**
     * Cast setting value to int, if it's a boolean number.
     *
     * @param string $value
     * @return int|string
     */
    public function getValueAttribute($value)
    {
        if ($value === '0' || $value === '1') {
            return (int) $value;
        }

        return $value;
    }

    /**
     * Always cast value to string to avoid issues
     * with large numbers and floats.
     *
     * @param $value
     */
    public function setValueAttribute($value)
    {
        if ($value) $value = (string) $value;
        $this->attributes['value'] = $value;
    }
}

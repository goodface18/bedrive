<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * ShareableLink
 *
 * @mixin \Eloquent
 * @property int $user_id
 * @property int $entry_id
 * @property boolean $allow_edit
 * @property boolean $allow_download
 * @property string $password
 * @property-read  FileEntry $entry
 */
class ShareableLink extends Model
{
    protected $guarded = ['id'];

    protected $dates = ['expires_at'];

    protected $casts = [
        'user_id' => 'integer',
        'entry_id' => 'integer',
        'id' => 'integer',
        'allow_download' => 'boolean',
        'allow_edit' => 'boolean'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function entry()
    {
        return $this->belongsTo(FileEntry::class);
    }

    /**
     * @param string $value
     */
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = $value ? bcrypt($value) : null;
    }
}

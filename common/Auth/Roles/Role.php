<?php namespace Common\Auth\Roles;

use Illuminate\Database\Eloquent\Model;
use Common\Auth\FormatsPermissions;

/**
 * @property integer $id
 * @property string $name
 * @property array $permissions
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property boolean $default
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\User[] $users
 * @mixin \Eloquent
 * @property int $guests
 */
class Role extends Model
{
    use FormatsPermissions;

    /**
     * The attributes that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden   = ['pivot'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'default' => 'boolean', 'guests' => 'boolean'];

    /**
     * Get default role for assigning to new users.
     *
     * @return Role|null
     */
    public function getDefaultRole()
    {
        return $this->where('default', 1)->first();
    }

    /**
     * Users belonging to this role.
     */
    public function users()
    {
        return $this->belongsToMany('App\User', 'user_role');
    }
}

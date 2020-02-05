<?php namespace Common\Auth;

use Common\Auth\Roles\Role;
use Common\Billing\Billable;
use Common\Billing\BillingPlan;
use Common\Files\Traits\SetsAvailableSpaceAttribute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Common\Files\FileEntry;
use Common\Files\UserFileEntry;
use Illuminate\Support\Arr;

/**
 * @property int $id
 * @property string|null $username
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $gender
 * @property array $permissions
 * @property string $email
 * @property string $password
 * @property integer|null $available_space
 * @property string|null $remember_token
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property int $stripe_active
 * @property string|null $stripe_id
 * @property string|null $stripe_subscription
 * @property string|null $stripe_plan
 * @property string|null $last_four
 * @property string|null $trial_ends_at
 * @property string|null $subscription_ends_at
 * @property int $confirmed
 * @property string|null $confirmation_code
 * @property string $avatar
 * @property-read string $display_name
 * @property-read mixed $followers_count
 * @property-read bool $has_password
 * @property-read bool $is_admin
 * @property-read \Illuminate\Database\Eloquent\Collection|\Common\Auth\Roles\Role[] $roles
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @mixin \Eloquent
 */
abstract class BaseUser extends Authenticatable
{
    use Notifiable, FormatsPermissions, Billable, SetsAvailableSpaceAttribute;

    protected $guarded = [];
    protected $hidden = ['password', 'remember_token', 'pivot'];
    protected $casts = [ 'id' => 'integer', 'confirmed' => 'integer', 'available_space' => 'integer'];
    protected $appends = ['display_name', 'has_password'];
    protected $billingEnabled = true;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->billingEnabled = config('common.site.billing_enabled');

        if ($this->billingEnabled) {
            $this->with = ['subscriptions.plan.parent'];
        }
    }

    /**
     * Roles this user belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_role');
    }

    /**
     * @param array $options
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function entries($options = ['owner' => true])
    {
        $query = $this->belongsToMany(FileEntry::class, 'user_file_entry', 'user_id', 'file_entry_id')
            ->using(UserFileEntry::class)
            ->withPivot('owner', 'permissions');

        if (Arr::get($options, 'owner')) {
            $query->wherePivot('owner', true);
        }

        return $query->withTimestamps()->orderBy('user_file_entry.created_at', 'asc');
    }

    /**
     * Social profiles this users account is connected to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function social_profiles()
    {
        return $this->hasMany(SocialProfile::class);
    }

    /**
     * Get user avatar.
     *
     * @return string
     */
    public function getAvatarAttribute()
    {
        $value = $this->attributes['avatar'];

        // absolute link
        if ($value && str_contains($value, '//')) return $value;

        //relative link (for new and legacy urls)
        if ($value) {
            return str_contains($value, 'assets') ? url($value) : url("storage/$value");
        }

        // gravatar
        $hash = md5(trim(strtolower($this->email)));

        return "https://www.gravatar.com/avatar/$hash?s=65";
    }

    /**
     * Compile user display name from available attributes.
     *
     * @return string
     */
    public function getDisplayNameAttribute()
    {
        if ($this->first_name && $this->last_name) {
            return $this->first_name.' '.$this->last_name;
        } else if ($this->first_name) {
            return $this->first_name;
        } else if ($this->last_name) {
            return $this->last_name;
        } else {
            return explode('@', $this->email)[0];
        }
    }

    /**
     * Check if user has a password set.
     *
     * @return bool
     */
    public function getHasPasswordAttribute()
    {
        return isset($this->attributes['password']) && $this->attributes['password'];
    }

    /**
     * Check if user has a specified permission.
     *
     * @param string $permission
     * @return bool
     */
    public function hasPermission($permission)
    {
        $permissions = $this->permissions;

        // merge role permissions
        foreach($this->roles as $role) {
            $permissions = array_merge($role->permissions, $permissions);
        }

        // merge billing plan permissions
        if ($this->billingEnabled) {
            if ($subscription = $this->subscriptions->first()) {
                $permissions = array_merge($subscription->plan ? $subscription->plan->permissions : [], $permissions);
            } else if ($freePlan = BillingPlan::where('free', true)->first()) {
                $permissions = array_merge($freePlan->permissions, $permissions);
            }
        }

        if (array_key_exists('admin', $permissions) && $permissions['admin']) return true;

        return array_key_exists($permission, $permissions) && $permissions[$permission];
    }

    public function setPermissionsAttribute($value)
    {
        $this->attributes['permissions'] = json_encode($value);
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeCompact(Builder $query)
    {
        return $query->select('users.id', 'users.avatar', 'users.email', 'users.first_name', 'users.last_name', 'users.username');
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPassword($token));
    }
}

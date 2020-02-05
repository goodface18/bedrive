<?php namespace Common\Billing;

use Common\Files\Traits\SetsAvailableSpaceAttribute;
use Common\Auth\FormatsPermissions;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property int $amount
 * @property string $currency
 * @property string $interval
 * @property string $interval_count
 * @property integer $parent_id
 * @property boolean $free
 * @property integer $available_space
 * @property string $uuid
 * @property string $paypal_id
 * @property string $features
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read BillingPlan $parent
 * @mixin \Eloquent
 */
class BillingPlan extends Model
{
    use FormatsPermissions, SetsAvailableSpaceAttribute;

    protected $guarded = ['id'];

    protected $casts = [
        'amount' => 'integer',
        'interval_count' => 'integer',
        'recommended' => 'boolean',
        'free' => 'boolean',
        'show_permissions' => 'boolean',
        'position' => 'integer',
        'available_space' => 'integer',
        'parent_id' => 'integer',
    ];

    public function getFeaturesAttribute($value)
    {
        if ($this->parent_id && $this->parent) {
            return $this->parent->features;
        }

        return json_decode($value, true) ?: [];
    }

    public function getPermissionsAttribute($value)
    {
        if ($this->parent_id && $this->parent) {
            return $this->parent->getPermissionsAttribute($value);
        }

        return json_decode($value, true) ?: [];
    }

    public function setFeaturesAttribute($value)
    {
        if (is_string($value)) return;
        $this->attributes['features'] = json_encode($value);
    }

    public function parent()
    {
        return $this->belongsTo(BillingPlan::class, 'parent_id');
    }
}

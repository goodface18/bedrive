<?php namespace Common\Billing;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Trait Billable
 * @property-read \Illuminate\Database\Eloquent\Collection|\Common\Billing\Subscription $subscriptions
 */
trait Billable
{
    public function subscribe($gateway, $gatewayId, BillingPlan $plan)
    {
        //TODO: calc based on plan interval
        $renewsAt = Carbon::now()->addMonths(1 * $plan->interval_count);

        $this->subscriptions()->create([
            'plan_id' => $plan->id,
            'ends_at' => null,
            'renews_at' => $renewsAt,
            'gateway' => $gateway,
            'gateway_id' => $gatewayId,
        ]);

        $this->load('subscriptions');
    }

    /**
     * Determine if user is subscribed.
     *
     * @return bool
     */
    public function subscribed()
    {
        $subscription = $this->subscriptions->first(function(Subscription $sub) {
            return $sub->valid();
        });

        return ! is_null($subscription);
    }

    /**
     * Check if user is subscribed to specified plan and gateway.
     *
     * @param BillingPlan $plan
     * @param string $gateway
     * @return bool
     */
    public function subscribedTo(BillingPlan $plan, $gateway) {
        return ! is_null($this->subscriptions->first(function(Subscription $sub) use($plan, $gateway) {
            return $sub->valid && $sub->plan_id === $plan->id && $sub->gateway === $gateway;
        }));
    }

    /**
     * @return HasMany
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'user_id');
    }
}

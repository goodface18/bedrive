<?php namespace Common\Billing\Gateways\Stripe;

use Common\Billing\BillingPlan;
use Omnipay\Stripe\Gateway;
use Common\Billing\GatewayException;
use Common\Billing\Gateways\Contracts\GatewayPlansInterface;

class StripePlans implements GatewayPlansInterface
{

    /**
     * @var Gateway
     */
    private $gateway;

    /**
     * StripePlans constructor.
     *
     * @param Gateway $gateway
     */
    public function __construct(Gateway $gateway)
    {
        $this->gateway = $gateway;
    }

    /**
     * Find specified plan on stripe.
     *
     * @param BillingPlan $plan
     * @return array|null
     */
    public function find(BillingPlan $plan)
    {
        $response = $this->gateway->fetchPlan(['id' => $plan->uuid])->send();

        if ( ! $response->isSuccessful()) return null;

        return $response->getData();
    }

    /**
     * Create a new plan on stripe gateway.
     *
     * @param BillingPlan $plan
     * @return bool
     * @throws GatewayException
     */
    public function create(BillingPlan $plan)
    {
        $params = [
            'id' => $plan->uuid,
            'amount' => $plan->amount,
            'currency' => $plan->currency,
            'interval' => $plan->interval,
            'interval_count' => $plan->interval_count,
            'nickname' => $plan->name,
            'name' => $plan->name,
        ];

        if ($plan->parent) {
            $params['product'] = $plan->parent->uuid;
        } else {
            $params['product'] = [
                'id' => $plan->uuid,
                'name' => $plan->name,
            ];
        }

        //TODO: fix this when omnipay stripe package is updated
        $r = new \ReflectionMethod(Gateway::class, 'createRequest');
        $r->setAccessible(true);
        $response = $r->invoke($this->gateway, StripeCreatePlanRequest::class, $params)->send();

        if ( ! $response->isSuccessful()) {
            throw new GatewayException($response->getMessage());
        }

        return true;
    }

    /**
     * Delete specified billing plan from currently active gateway.
     *
     * @param BillingPlan $plan
     * @return bool
     */
    public function delete(BillingPlan $plan)
    {
        return $this->gateway->deletePlan(['id' => $plan->uuid])->send()->isSuccessful();
    }
}
<?php namespace Common\Billing\Gateways\Contracts;

use Common\Billing\BillingPlan;
use Common\Billing\GatewayException;

interface GatewayPlansInterface
{
    /**
     * Find specified plan on gateway.
     *
     * @param BillingPlan $plan
     * @return array
     */
    public function find(BillingPlan $plan);

    /**
     * Create a new subscription plan on gateway.
     *
     * @param BillingPlan $plan
     * @throws GatewayException
     * @return bool
     */
    public function create(BillingPlan $plan);

    /**
     * Delete specified subscription plan from gateway.
     *
     * @param BillingPlan $plan
     * @throws GatewayException
     * @return bool
     */
    public function delete(BillingPlan $plan);
}
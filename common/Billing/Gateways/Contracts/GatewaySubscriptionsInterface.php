<?php namespace Common\Billing\Gateways\Contracts;

use Common\Billing\BillingPlan;
use Common\Billing\GatewayException;
use Common\Billing\Subscription;
use App\User;

interface GatewaySubscriptionsInterface
{
    /**
     * Fetch specified subscription's details from gateway.
     *
     * @param Subscription $subscription
     * @return array
     */
    public function find(Subscription $subscription);

    /**
     * Cancel specified subscription on gateway.
     *
     * @param Subscription $subscription
     * @param bool $atPeriodEnd
     * @return boolean
     */
    public function cancel(Subscription $subscription, $atPeriodEnd = false);

    /**
     * Resume specified subscription on gateway.
     *
     * @param Subscription $subscription
     * @param array $params
     * @return bool
     * @throws GatewayException
     */
    public function resume(Subscription $subscription, $params);

    /**
     * Create a new subscription or subscription agreement on gateway.
     *
     * @param BillingPlan $plan
     * @param User $user
     * @param string|integer $startDate
     * @return array
     * @throws GatewayException
     * @throws \LogicException
     */
    public function create(BillingPlan $plan, User $user, $startDate = null);

    /**
     * Change billing plan of specified subscription.
     *
     * @param Subscription $subscription
     * @param BillingPlan $newPlan
     * @return mixed
     */
    public function changePlan(Subscription $subscription, BillingPlan $newPlan);
}
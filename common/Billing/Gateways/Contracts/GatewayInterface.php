<?php namespace Common\Billing\Gateways\Contracts;

use Illuminate\Http\Request;

interface GatewayInterface
{
    /**
     * @return GatewaySubscriptionsInterface
     */
    public function subscriptions();

    /**
     * @return GatewayPlansInterface
     */
    public function plans();

    /**
     * Check if specified webhook is valid.
     *
     * @param Request $request
     * @return bool
     */
    public function webhookIsValid(Request $request);
}
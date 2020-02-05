<?php namespace Common\Billing\Gateways\Paypal;

use Common\Billing\BillingPlan;
use Omnipay\PayPal\RestGateway;
use Common\Billing\GatewayException;
use Common\Billing\Gateways\Contracts\GatewayPlansInterface;

class PaypalPlans implements GatewayPlansInterface
{
    /**
     * @var RestGateway
     */
    private $gateway;

    /**
     * PaypalPlans constructor.
     *
     * @param RestGateway $gateway
     */
    public function __construct(RestGateway $gateway)
    {
        $this->gateway = $gateway;
    }

    /**
     * Find specified plan on paypal.
     *
     * @param BillingPlan $plan
     * @param int $page
     * @return array|null
     */
    public function find(BillingPlan $plan, $page = 0)
    {
        if ($plan->paypal_id) {
            $response = $this->gateway
                ->createRequest(PaypalFetchPlanRequest::class)
                ->setPlanId($plan->paypal_id)
                ->send(['planId' => $plan->paypal_id]);

            $paypalPlan = $response->getData();

            return empty($paypalPlan) ? null : $paypalPlan;
        }

        // legacy, before paypal plan ID was stored on billing plan model

        $response = $this->gateway->listPlan(
            ['pageSize' => 20, 'page' => $page, 'totalRequired' => 'yes', 'status' => RestGateway::BILLING_PLAN_STATE_ACTIVE]
        )->send();

        // there are no plans created on paypal at all
        if ( ! isset($response->getData()['plans'])) return null;

        // match plan by UUID stored in description
        $paypalPlan = collect($response->getData()['plans'])->first(function ($paypalPlan) use ($plan) {
            return $paypalPlan['description'] === $plan->uuid;
        });

        // found a match
        if ($paypalPlan) return $paypalPlan;

        // if there are more plans to paginate, do a recursive loop
        if ($page < (int) $response->getData()['total_pages']) {
            return $this->find($plan, $page + 1);
        }

        // count not find matching plan
        return null;
    }

    /**
     * Get specified plan's PayPal ID.
     *
     * @param BillingPlan $plan
     * @return string
     * @throws GatewayException
     */
    public function getPlanId(BillingPlan $plan)
    {
        if ($plan->paypal_id) {
            return $plan->paypal_id;
        }

        // legacy, before paypal plan ID was stored on billing plan model
        if ( ! $paypalPlan = $this->find($plan)) {
            throw new GatewayException("Could not find plan '{$plan->name}' on paypal");
        }

        return $paypalPlan['id'];
    }

    /**
     * Create a new subscription plan on paypal.
     *
     * @param BillingPlan $plan
     * @throws GatewayException
     * @return bool
     */
    public function create(BillingPlan $plan)
    {
        $response = $this->gateway->createPlan([
            'name'  => $plan->name,
            'description'  => $plan->uuid,
            'type' => RestGateway::BILLING_PLAN_TYPE_INFINITE,
            'paymentDefinitions' => [
                [
                    'name'               => $plan->name.' definition',
                    'type'               => RestGateway::PAYMENT_REGULAR,
                    'frequency'          => strtoupper($plan->interval),
                    'frequency_interval' => $plan->interval_count,
                    'cycles'             => 0,
                    'amount'             => ['value' => $plan->amount, 'currency' => strtoupper($plan->currency)],
                ],
            ],
            'merchant_preferences' => [
                'return_url' => url('billing/paypal/callback/approved'),
                'cancel_url' => url('billing/paypal/callback/canceled'),
                'auto_bill_amount' => 'YES',
                'initial_fail_amount_action' => 'CONTINUE',
                'max_fail_attempts' => '3',
            ]
        ])->send();

        if ( ! $response->isSuccessful()) {
            throw new GatewayException($response->getMessage());
        }

        $paypalId = $response->getData()['id'];

        //set plan to active on paypal
        $response = $this->gateway->updatePlan([
            'state' => RestGateway::BILLING_PLAN_STATE_ACTIVE,
            'transactionReference' => $paypalId,
        ])->send();

        if ( ! $response->isSuccessful()) {
            throw new GatewayException($response->getMessage());
        }

        $plan->fill(['paypal_id' => $paypalId])->save();

        return true;
    }

    /**
     * Delete specified billing plan from currently active gateway.
     *
     * @param BillingPlan $plan
     * @return bool
     * @throws GatewayException
     */
    public function delete(BillingPlan $plan)
    {
        return $this->gateway->updatePlan([
            'transactionReference' => $this->getPlanId($plan),
            'state' => RestGateway::BILLING_PLAN_STATE_DELETED
        ])->send()->isSuccessful();
    }
}
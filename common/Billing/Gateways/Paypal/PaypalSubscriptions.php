<?php namespace Common\Billing\Gateways\Paypal;

use Common\Billing\BillingPlan;
use Common\Billing\GatewayException;
use Common\Billing\Gateways\Contracts\GatewaySubscriptionsInterface;
use Common\Billing\Subscription;
use Carbon\Carbon;
use Omnipay\PayPal\RestGateway;
use App\User;

class PaypalSubscriptions implements GatewaySubscriptionsInterface
{
    /**
     * @var RestGateway
     */
    private $gateway;

    /**
     * @var PaypalPlans
     */
    private $paypalPlans;

    /**
     * PaypalPlans constructor.
     * @param RestGateway $gateway
     * @param PaypalPlans $paypalPlans
     */
    public function __construct(RestGateway $gateway, PaypalPlans $paypalPlans)
    {
        $this->gateway = $gateway;
        $this->paypalPlans = $paypalPlans;
    }

    /**
     * Fetch specified subscription's details from paypal.
     *
     * @param Subscription $subscription
     * @return array
     * @throws GatewayException
     */
    public function find(Subscription $subscription)
    {
        $response = $this->gateway->createRequest(PaypalFetchBillingAgreementRequest::class, [
            'transactionReference' => $subscription->gateway_id
        ])->send();

        if ( ! $response->isSuccessful()) {
            throw new GatewayException("Could not find paypal subscription: {$response->getMessage()}");
        }

        return [
            'renews_at' => Carbon::parse($response->getData()['agreement_details']['next_billing_date']),
        ];
    }

    /**
     * Create subscription agreement on paypal.
     *
     * @param BillingPlan $plan
     * @param User $user
     * @param string|null $startDate
     * @return array
     * @throws GatewayException
     */
    public function create(BillingPlan $plan, User $user, $startDate = null)
    {
        $response = $this->gateway->createSubscription([
            'name'        => config('app.name')." subscription: {$plan->name}.",
            'description' => "{$plan->name} subscription on ".config('app.name'),
            'planId' => $this->paypalPlans->getPlanId($plan),
            'startDate' => $startDate ? Carbon::parse($startDate) : Carbon::now()->addMinute(),
            'payerDetails' => ['payment_method' => 'paypal'],
        ])->send();

        if ( ! $response->isSuccessful() || ! $response->isRedirect()) {
            throw new GatewayException('Could not create subscription agreement on paypal');
        }

        if ($this->gateway->getTestMode()) {
            $uri = 'https://www.sandbox.paypal.com';
        } else {
            $uri = 'https://www.paypal.com';
        }

        return [
            'approve' => "$uri/checkoutnow?version=4&token={$response->getTransactionReference()}",
            'execute' => $response->getCompleteUrl(),
        ];
    }

    /**
     * Immediately cancel subscription agreement on paypal.
     *
     * @param Subscription $subscription
     * @param bool $atPeriodEnd
     * @return bool
     * @throws GatewayException
     */
    public function cancel(Subscription $subscription, $atPeriodEnd = false)
    {
        $response = $this->gateway->suspendSubscription([
            'transactionReference' => $subscription->gateway_id,
            'description' => 'Cancelled by user.'
        ])->send();

        if ( ! $response->isSuccessful()) {
            throw new GatewayException("Paypal sub cancel failed: {$response->getMessage()}");
        }

        return true;
    }

    /**
     * Resume specified subscription on paypal.
     *
     * @param Subscription $subscription
     * @param array $params
     * @return bool
     * @throws GatewayException
     */
    public function resume(Subscription $subscription, $params)
    {
        $response = $this->gateway->reactivateSubscription([
            'transactionReference' => $subscription->gateway_id,
            'description' => 'Resumed by user.'
        ])->send();

        if ( ! $response->isSuccessful()) {
            throw new GatewayException("Paypal sub resume failed: {$response->getMessage()}");
        }

        return true;
    }

    /**
     * Change billing plan of specified subscription.
     *
     * @param Subscription $subscription
     * @param BillingPlan $newPlan
     * @return array
     */
    public function changePlan(Subscription $subscription, BillingPlan $newPlan)
    {
        //TODO: implement when paypal fully supports billing agreement plan change. In the meantime
        // it's done on the front-end by cancelling user subscription and then creating a new one.

        return [];
    }

    /**
     * Execute paypal subscription agreement.
     *
     * @param string $agreementId
     * @return string
     * @throws GatewayException
     */
    public function executeAgreement($agreementId)
    {
        $response = $this->gateway->completeSubscription([
            'transactionReference' => $agreementId
        ])->send();

        if ( ! $response->isSuccessful()) {
            throw new GatewayException("Paypal sub agreement execute failed: {$response->getMessage()}");
        }

        return $response->getTransactionReference();
    }
}
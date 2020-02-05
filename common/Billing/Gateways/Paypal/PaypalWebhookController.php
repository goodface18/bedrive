<?php namespace Common\Billing\Gateways\Paypal;

use Common\Billing\Subscription;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Common\Billing\Gateways\GatewayFactory;
use Illuminate\Support\Arr;

class PaypalWebhookController extends Controller
{
    /**
     * @var PaypalGateway
     */
    private $gateway;

    /**
     * @var Subscription
     */
    private $subscription;

    /**
     * PaypalWebhookController constructor.
     *
     * @param GatewayFactory $gatewayFactory
     * @param Subscription $subscription
     */
    public function __construct(GatewayFactory $gatewayFactory, Subscription $subscription)
    {
        $this->gateway = $gatewayFactory->get('paypal');
        $this->subscription = $subscription;
    }

    /**
     * Handle a paypal webhook call.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleWebhook(Request $request)
    {
        $payload = $request->all();

        if ( ! $this->gateway->webhookIsValid($request)) {
            return response('Webhook validation failed', 422);
        };

        switch ($payload['event_type']) {
            case 'BILLING.SUBSCRIPTION.CANCELLED':
            case 'BILLING.SUBSCRIPTION.EXPIRED':
                return $this->handleSubscriptionCancelled($payload);
            case 'PAYMENT.SALE.COMPLETED':
                return $this->handleSubscriptionRenewed($payload);
            default:
                return response('Webhook Handled', 200);
        }
    }

    /**
     * Handle a cancelled customer from a paypal subscription.
     *
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleSubscriptionCancelled($payload)
    {
        $gatewayId = $payload['resource']['id'];

        $subscription = $this->subscription->where('gateway_id', $gatewayId)->first();

        if ($subscription && ! $subscription->cancelled()) {
            $subscription->markAsCancelled();
        }

        return response('Webhook Handled', 200);
    }

    /**
     * Handle a renewed stripe subscription.
     *
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleSubscriptionRenewed($payload)
    {
        $gatewayId = Arr::get($payload, 'resource.billing_agreement_id');

        $subscription = $this->subscription->where('gateway_id', $gatewayId)->first();

        if ($subscription) {
            $paypalSubscription = $this->gateway->subscriptions()->find($subscription);
            $subscription->fill(['renews_at' => $paypalSubscription['renews_at']])->save();
        }

        return response('Webhook Handled', 200);
    }
}

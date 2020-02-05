<?php namespace Common\Billing\Webhooks;

use Common\Billing\Subscription;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Common\Billing\Gateways\GatewayFactory;
use Common\Billing\Gateways\Stripe\StripeGateway;

class StripeWebhookController extends Controller
{
    /**
     * @var StripeGateway
     */
    private $gateway;

    /**
     * @var Subscription
     */
    private $subscription;

    /**
     * StripeWebhookController constructor.
     *
     * @param GatewayFactory $gatewayFactory
     * @param Subscription $subscription
     */
    public function __construct(GatewayFactory $gatewayFactory, Subscription $subscription)
    {
        $this->gateway = $gatewayFactory->get('stripe');
        $this->subscription = $subscription;
    }

    /**
     * Handle a Stripe webhook call.
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

        switch ($payload['type']) {
            case 'customer.subscription.deleted':
                return $this->handleSubscriptionDeleted($payload);
            case 'invoice.payment_succeeded':
                return $this->handleSubscriptionRenewed($payload);
            default:
                return response();
        }
    }

    /**
     * Handle a cancelled customer from a Stripe subscription.
     *
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleSubscriptionDeleted($payload)
    {
        $gatewayId = $payload['data']['object']['id'];

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
        $gatewayId = $payload['data']['object']['subscription'];

        $subscription = $this->subscription->where('gateway_id', $gatewayId)->first();

        if ($subscription) {
            $stripeSubscription = $this->gateway->subscriptions()->find($subscription);
            $subscription->fill(['renews_at' => $stripeSubscription['renews_at']])->save();
        }

        return response('Webhook Handled', 200);
    }
}

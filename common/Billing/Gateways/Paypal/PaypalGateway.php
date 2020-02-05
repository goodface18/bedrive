<?php namespace Common\Billing\Gateways\Paypal;

use Omnipay\Omnipay;
use Illuminate\Http\Request;
use Omnipay\PayPal\RestGateway;
use Common\Billing\GatewayException;
use Common\Billing\Gateways\Contracts\GatewayInterface;
use Common\Settings\Settings;

class PaypalGateway implements GatewayInterface
{
    /**
     * @var RestGateway
     */
    private $gateway;

    /**
     * @var PaypalPlans
     */
    private $plans;

    /**
     * @var PaypalSubscriptions
     */
    private $subscriptions;

    /**
     * @param Settings $settings
     */
    public function __construct(Settings $settings)
    {
        $this->gateway = Omnipay::create('PayPal_Rest');

        $this->gateway->initialize([
            'clientId' => config('services.paypal.client_id'),
            'secret' => config('services.paypal.secret'),
            'testMode' => $settings->get('billing.paypal_test_mode'),
        ]);

        $this->plans = new PaypalPlans($this->gateway);
        $this->subscriptions = new PaypalSubscriptions($this->gateway, $this->plans);
    }

    /**
     * Get paypal plans service instance.
     * 
     * @return PaypalPlans
     */
    public function plans()
    {
        return $this->plans;
    }

    /**
     * Get paypal subscriptions service instance.
     * 
     * @return PaypalSubscriptions
     */
    public function subscriptions()
    {
        return $this->subscriptions;
    }

    /**
     * Check if specified webhook is valid.
     *
     * @param Request $request
     * @return bool
     * @throws GatewayException
     * @throws \Omnipay\Common\Exception\InvalidResponseException
     */
    public function webhookIsValid(Request $request)
    {
        $payload = [
            'auth_algo' => $request->header('PAYPAL-AUTH-ALGO'),
            'cert_url' => $request->header('PAYPAL-CERT-URL'),
            'transmission_id' => $request->header('PAYPAL-TRANSMISSION-ID'),
            'transmission_sig' => $request->header('PAYPAL-TRANSMISSION-SIG'),
            'transmission_time' => $request->header('PAYPAL-TRANSMISSION-TIME'),
            'webhook_id' => config('services.paypal.webhook_id'),
            'webhook_event' => $request->all(),
        ];

        $response = $this->gateway->createRequest(PaypalVerifyWebhookRequest::class)->sendData($payload);

        if ( ! $response->isSuccessful()) {
            throw new GatewayException("Could not validate paypal webhook: {$response->getMessage()}");
        }

        return $response->getData()['verification_status'] === 'SUCCESS';
    }
}
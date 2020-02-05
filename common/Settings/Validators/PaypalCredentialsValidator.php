<?php

namespace Common\Settings\Validators;

use Common\Settings\Settings;
use Config;
use GuzzleHttp\Exception\ServerException;
use Omnipay\Omnipay;
use Omnipay\PayPal\RestGateway;
use Illuminate\Support\Arr;
use GuzzleHttp\Exception\ClientException;

class PaypalCredentialsValidator implements SettingsValidator
{
    const KEYS = [
        'paypal_client_id',
        'paypal_secret',
        'paypal_webhook_id',
        'billing.paypal_test_mode'
    ];

    /**
     * @var Settings
     */
    private $settings;

    /**
     * @param Settings $settings
     */
    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    public function fails($settings)
    {
        $this->setConfigDynamically($settings);

        // create gateway after setting config dynamically
        // so gateway uses new configuration
        $gateway = $this->createGateway();

        try {
            $response = $gateway->listPlan(
                ['pageSize' => 20, 'page' => 1, 'totalRequired' => 'yes']
            )->send();

            if ( ! $response->isSuccessful()) {
                return $this->getErrorMessage($response->getData());
            }
        } catch (ClientException $e) {
            return $this->getDefaultError();
        } catch (ServerException $e) {
            return $this->getDefaultError();
        }
    }

    private function createGateway()
    {
        /** @var RestGateway $gateway */
        $gateway = Omnipay::create('PayPal_Rest');

        $gateway->initialize([
            'clientId' => config('services.paypal.client_id'),
            'secret' => config('services.paypal.secret'),
            'testMode' => $this->settings->get('billing.paypal_test_mode'),
        ]);

        return $gateway;
    }

    private function setConfigDynamically($settings)
    {
        foreach (self::KEYS as $key) {
            if ( ! Arr::has($settings, $key)) continue;

            if ($key === 'billing.paypal_test_mode') {
                $this->settings->set('billing.paypal_test_mode', $settings[$key]);
            } else {
                // paypal_client_id => client_id
                $configKey = str_replace('paypal_', '', $key);
                Config::set("services.paypal.$configKey", $settings[$key]);
            }
        }
    }

    /**
     * @param array $data
     * @return array
     */
    private function getErrorMessage($data)
    {
        $type = $data['name'];

        switch ($type) {
            case 'AUTHENTICATION_FAILURE':
                return ['paypal_group' => 'Paypal Client ID or Paypal Secret is invalid.'];
            default:
                return $this->getDefaultError();
        }
    }

    private function getDefaultError()
    {
        return ['paypal_group' => 'These paypal credentials are not valid.'];
    }
}
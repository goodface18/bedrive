<?php

namespace Common\Settings\Validators;

use Common\Settings\Settings;
use Config;
use GuzzleHttp\Exception\ServerException;
use Omnipay\Omnipay;
use Omnipay\PayPal\RestGateway;
use Illuminate\Support\Arr;
use GuzzleHttp\Exception\ClientException;

class StripeCredentialsValidator implements SettingsValidator
{
    const KEYS = [
        'stripe_key',
        'stripe_secret',
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
            $response = $gateway->listPlans()->send();

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
        /** @var \Omnipay\Stripe\Gateway $gateway */
        $gateway = Omnipay::create('Stripe');

        $gateway->initialize(array(
            'apiKey' => config('services.stripe.secret'),
        ));

        return $gateway;
    }

    private function setConfigDynamically($settings)
    {
        foreach (self::KEYS as $key) {
            if ( ! Arr::has($settings, $key)) continue;

            // stripe_key => key
            $configKey = str_replace('stripe_', '', $key);
            Config::set("services.stripe.$configKey", $settings[$key]);
        }
    }

    /**
     * @param array $data
     * @return array
     */
    private function getErrorMessage($data)
    {
        switch (Arr::get($data, 'error.type')) {
            case 'invalid_request_error':
                return ['stripe_secret' => 'Stripe Secret is invalid.'];
            default:
                return $this->getDefaultError();
        }
    }

    private function getDefaultError()
    {
        return ['stripe_group' => 'These stripe credentials are not valid.'];
    }
}
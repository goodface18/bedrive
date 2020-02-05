<?php

namespace Common\Settings\Validators;

use Config;
use Socialite;
use Common\Auth\Oauth;
use Illuminate\Support\Arr;
use Common\Core\HttpClient;
use GuzzleHttp\Exception\ClientException;

class GoogleLoginValidator implements SettingsValidator
{
    const KEYS = ['google_id', 'google_secret'];

    /**
     * @var Oauth
     */
    private $oauth;

    /**
     * @var HttpClient
     */
    private $httpClient;

    public function __construct(Oauth $oauth)
    {
        $this->oauth = $oauth;
        $this->httpClient = new HttpClient([
            'exceptions' => true,
        ]);
    }

    public function fails($settings)
    {
        $this->setConfigDynamically($settings);

        try {
            Socialite::driver('google')->getAccessTokenResponse('foo-bar');
        } catch (ClientException $e) {
            return $this->getErrorMessage($e);
        }
    }

    private function setConfigDynamically($settings)
    {
        if ($googleId = Arr::get($settings, 'google_id')) {
            Config::set('services.google.client_id', $googleId);
        }

        if ($googleSecret = Arr::get($settings, 'google_secret')) {
            Config::set('services.google.client_secret', $googleSecret);
        }
    }

    /**
     * @param ClientException $e
     * @return array
     */
    private function getErrorMessage(ClientException $e)
    {
        $errResponse = json_decode($e->getResponse()->getBody()->getContents(), true);

        // there were no credentials related errors, we can assume validation was successful
        if (Arr::get($errResponse, 'error_description') === 'Malformed auth code.') {
            return null;
        }

        $message = strtolower(Arr::get($errResponse, 'error.errors.0.message', ''));
        return ['google_group' => 'These google credentials are not valid.'];
    }
}
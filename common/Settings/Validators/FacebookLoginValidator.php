<?php

namespace Common\Settings\Validators;

use Config;
use GuzzleHttp\Exception\ServerException;
use Socialite;
use Common\Auth\Oauth;
use Illuminate\Support\Arr;
use Common\Core\HttpClient;
use GuzzleHttp\Exception\ClientException;

class FacebookLoginValidator implements SettingsValidator
{
    const KEYS = ['facebook_id', 'facebook_secret'];

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
            Socialite::driver('facebook')->getAccessTokenResponse('foo-bar');
        } catch (ClientException $e) {
            return $this->getErrorMessage($e);
        } catch (ServerException $e) {
            return $this->getDefaultError();
        }
    }

    private function setConfigDynamically($settings)
    {
        if ($facebookId = Arr::get($settings, 'facebook_id')) {
            Config::set('services.facebook.client_id', $facebookId);
        }

        if ($facebookSecret = Arr::get($settings, 'facebook_secret')) {
            Config::set('services.facebook.client_secret', $facebookSecret);
        }
    }

    /**
     * @param ClientException $e
     * @return array
     */
    private function getErrorMessage(ClientException $e)
    {
        $errResponse = json_decode($e->getResponse()->getBody()->getContents(), true);
        $code = Arr::get($errResponse, 'error.code');

        // there were no credentials related errors, we can assume validation was successful
        if ($code === 100) {
            return null;
        }

        if ($code === 191) {
            return ['facebook_group' => 'Site url is not present in "Valid OAuth Redirect URIs" field on your facebook app.'];
        }

        return $this->getDefaultError();
    }

    private function getDefaultError()
    {
        return ['facebook_group' => 'These facebook credentials are not valid.'];
    }
}
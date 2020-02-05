<?php

namespace Common\Settings\Validators;

use Config;
use Exception;
use Illuminate\Support\Arr;
use Socialite;
use Common\Auth\Oauth;
use Common\Core\HttpClient;

class TwitterLoginValidator implements SettingsValidator
{
    const KEYS = ['twitter_id', 'twitter_secret'];

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
            Socialite::driver('twitter')->redirect();
        } catch (Exception $e) {
            return $this->getErrorMessage($e);
        }
    }

    private function setConfigDynamically($settings)
    {
        if ($twitterId = Arr::get($settings, 'twitter_id')) {
            Config::set('services.twitter.client_id', $twitterId);
        }

        if ($twitterSecret = Arr::get($settings, 'twitter_secret')) {
            Config::set('services.twitter.client_secret', $twitterSecret);
        }
    }

    /**
     * @param Exception $e
     * @return array
     */
    private function getErrorMessage(Exception $e)
    {
        if (str_contains($e->getMessage(), 'code="415"')) {
            return ['twitter_group' => 'Site url is not present in "Callback URL" field on your twitter app.'];
        }

        return $this->getDefaultError();
    }

    private function getDefaultError()
    {
        return ['twitter_group' => 'These twitter credentials are not valid.'];
    }
}
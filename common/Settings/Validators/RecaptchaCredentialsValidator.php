<?php

namespace Common\Settings\Validators;

use Exception;
use Illuminate\Support\Arr;
use Common\Core\HttpClient;

class RecaptchaCredentialsValidator
{
    const KEYS = ['recaptcha.site_key', 'recaptcha.secret_key'];

    /**
     * @var HttpClient
     */
    private $httpClient;

    public function __construct()
    {
        $this->httpClient = new HttpClient([
            'exceptions' => true
        ]);
    }

    public function fails($settings)
    {
        try {
            $response = $this->httpClient->post('https://www.google.com/recaptcha/api/siteverify', [
                'form_params' => [
                    'response' => 'foo-bar',
                    'secret' => Arr::get($settings, 'recaptcha.secret_key'),
                ]
            ]);
            if ($response['success'] === false && Arr::get($response, 'error-codes.1') === 'invalid-input-secret') {
                return ['recaptcha.secret_key' => 'This recaptcha secret key is not valid.'];
            }
        } catch (Exception $e) {
            return $this->getErrorMessage($e);
        }
    }

    /**
     * @param Exception $e
     * @return array
     */
    private function getErrorMessage($e)
    {
        return ['logging_group' => $e->getMessage()];
    }
}
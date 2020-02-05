<?php

namespace Common\Settings\Validators\MailCredentials;

use Auth;
use Config;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Arr;
use Mail;
use Exception;
use Common\Settings\Validators\SettingsValidator;

class MailCredentialsValidator implements SettingsValidator
{
    const KEYS = [
        'mail_driver',
        'mail_host', 'mail_username', 'mail_password', 'mail_port', 'mail_encryption', // SMTP
        'mailgun_domain', 'mailgun_secret', // Mailgun
        'ses_key', 'ses_secret', // Amazon SES
        'sparkpost_secret', // Sparkpost
    ];

    public function fails($settings)
    {
        $this->setConfigDynamically($settings);

        try {
           Mail::to(Auth::user()->email)->send(new MailCredentialsMailable());
        } catch (Exception $e) {
            return $this->getErrorMessage($e);
        }
    }

    private function setConfigDynamically($settings)
    {
        foreach ($settings as $key => $value) {
            //mail_host => mail.host
            $key = str_replace('_', '.', $key);

            // "mail.*" credentials go into "mail.php" config
            // file, other credentials go into "services.php"
            if ( ! starts_with($key, 'mail.')) {
                $key = "services.$key";
            }

            Config::set($key, $value);
        }
    }

    /**
     * @param Exception|ClientException $e
     * @return array
     */
    private function getErrorMessage($e)
    {
        $message = null;
        if (config('mail.driver') === 'smtp') {
            $message = $this->getSmtpMessage($e);
        } else if (config('mail.driver') === 'mailgun') {
            $message = $this->getMailgunMessage($e);
        }

        return $message ?: $this->getDefaultMessage();
    }

    private function getMailgunMessage(ClientException $e)
    {
        $originalContents = $e->getResponse()->getBody()->getContents();
        $errResponse = json_decode($originalContents, true);
        if (is_null($errResponse) && is_string($originalContents)) {
            $errResponse = $originalContents;
        }
        $message = strtolower(Arr::get($errResponse, 'message', $errResponse));

        if (str_contains($message, 'domain not found')) {
            return ['mailgun_domain' => 'This mailgun domain is not valid.'];
        } else if (str_contains($message, 'forbidden')) {
            return ['mailgun_secret' => 'This mailgun API Key is not valid.'];
        }

        return ['mail_group' => 'Could not validate mailgun credentials. Please double check them.'];
    }

    /**
     * @param Exception $e
     * @return array
     */
    private function getSmtpMessage(Exception $e)
    {
        if (str_contains($e->getMessage(), 'Connection timed out #110')) {
            return ['mail_group' => 'Connection to mail server timed out. This usually indicates incorrect mail credentials. Please double check them.'];
        }
    }

    private function getDefaultMessage()
    {
        return ['mail_group' => 'Could not validate mail credentials. Please double check them.'];
    }
}
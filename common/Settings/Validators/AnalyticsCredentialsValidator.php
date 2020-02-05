<?php

namespace Common\Settings\Validators;

use Config;
use Google_Auth_Exception;
use Illuminate\Support\Arr;
use Google_Service_Exception;
use Common\Admin\Analytics\Actions\GetGoogleAnalyticsData;

class AnalyticsCredentialsValidator
{
    const KEYS = ['analytics_view_id', 'analytics_service_email', 'analytics.tracking_code', 'certificate'];

    public function fails($settings)
    {
        $this->setConfigDynamically($settings);

        try {
            app(GetGoogleAnalyticsData::class)->execute();
        } catch (Google_Service_Exception $e) {
            return $this->getErrorMessage($e);
        } catch (Google_Auth_Exception $e) {
            return $this->getErrorMessage($e);
        }
    }

    private function setConfigDynamically($settings)
    {
        if ($viewId = Arr::get($settings, 'analytics_view_id')) {
            Config::set('laravel-analytics.siteId', "ga:$viewId");
        }

        if ($serviceEmail = Arr::get($settings, 'analytics_service_email')) {
            Config::set('laravel-analytics.serviceEmail', $serviceEmail);
        }
    }

    /**
     * @param Google_Service_Exception|Google_Auth_Exception $e
     * @return array
     */
    private function getErrorMessage($e)
    {
        if ($e instanceof Google_Service_Exception) {
            $message = Arr::get($e->getErrors(), '0.message');
        } else {
            $message = $e->getMessage();
        }

        return ['analytics_group' => 'Invalid credentials: ' . $message];
    }
}
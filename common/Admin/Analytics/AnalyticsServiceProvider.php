<?php

namespace Common\Admin\Analytics;

use Common\Admin\Analytics\Actions\GetNullAnalyticsData;
use Exception;
use Common\Admin\Analytics\Actions\GetAnalyticsData;
use Common\Admin\Analytics\Actions\GetGoogleAnalyticsData;
use Illuminate\Support\ServiceProvider;

class AnalyticsServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(GetAnalyticsData::class, function () {
            if (config('common.site.demo')) {
                return new GetDemoAnalyticsData();
            } else {
                return $this->getGoogleAnalyticsData();
            }
        });
    }

    /**
     * @return GetGoogleAnalyticsData|GetNullAnalyticsData
     */
    private function getGoogleAnalyticsData()
    {
        try {
            return $this->app->make(GetGoogleAnalyticsData::class);
        } catch (Exception $e) {
            if (str_contains($e->getMessage(), "Can't find the .p12 certificate")) {
                return new GetNullAnalyticsData();
            } else {
                throw($e);
            }
        }
    }
}
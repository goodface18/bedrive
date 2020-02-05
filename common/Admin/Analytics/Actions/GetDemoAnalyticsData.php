<?php

namespace Common\Admin\Analytics;

use Carbon\Carbon;
use Common\Admin\Analytics\Actions\GetAnalyticsData;

class GetDemoAnalyticsData implements GetAnalyticsData
{
    public function execute() {
        return [
            'weeklyPageViews' => [
                'current' => $this->getWeekly(Carbon::now()),
                'previous' => $this->getWeekly(Carbon::now()->subWeek()),
            ],
            'monthlyPageViews' => [
                'current' => $this->getMonthly(Carbon::now()),
                'previous' => $this->getMonthly(Carbon::now()->subMonth()),
            ],
            'browsers' => $this->getBrowsers(),
            'countries' => $this->getCountries()
        ];
    }

    /**
     * Get weekly page views for specified date.
     *
     * @param Carbon $date
     * @return array
     */
    private function getWeekly($date)
    {
        return $this->getPageViews(
            $date->startOfWeek(), 7
        );
    }

    /**
     * Get monthly page views for specified date.
     *
     * @param Carbon $date
     * @return array
     */
    private function getMonthly(Carbon $date)
    {
        return $this->getPageViews(
            $date->startOfMonth(), $date->daysInMonth
        );
    }

    /**
     * @param Carbon $date
     * @param int $daysCount
     * @return array
     */
    private function getPageViews(Carbon $date, $daysCount)
    {
        // remove one day because loop will start from day 2
        $date->subDay();

        $data = [];

        for ($i = 0; $i <= $daysCount - 1; $i++) {
            $data[$i] = [
                'date' => $date->addDay()->getTimestamp(),
                'pageViews' => random_int(100, 500)
            ];
        }

        return $data;
    }

    private function getBrowsers()
    {
        return [
            ['browser' => 'Chrome', 'sessions' => random_int(300, 500)],
            ['browser' => 'Firefox', 'sessions' => random_int(200, 400)],
            ['browser' => 'IE', 'sessions' => random_int(100, 150)],
            ['browser' => 'Edge', 'sessions' => random_int(100, 200)],
            ['browser' => 'Safari', 'sessions' => random_int(200, 300)],
        ];
    }

    private function getCountries()
    {
        return [
            ['country' => 'United States', 'sessions' => random_int(300, 500)],
            ['country' => 'India', 'sessions' => random_int(100, 300)],
            ['country' => 'Russia', 'sessions' => random_int(250, 400)],
            ['country' => 'Germany', 'sessions' => random_int(200, 500)],
            ['country' => 'France', 'sessions' => random_int(150, 300)],
        ];
    }
}
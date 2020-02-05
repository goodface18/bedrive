<?php

namespace Common\Admin\Analytics\Actions;

class GetNullAnalyticsData implements GetAnalyticsData
{
    public function execute() {
        return [
            'weeklyPageViews' => [
                'current' => [],
                'previous' => [],
            ],
            'monthlyPageViews' => [
                'current' => [],
                'previous' => [],
            ],
            'browsers' => [],
            'countries' => []
        ];
    }
}
<?php

namespace Common\Admin\Analytics\Actions;

use Illuminate\Support\Collection;

interface GetAnalyticsData
{
    /**
     * Get data for admin area analytics page from active provider.
     * (Demo or Google Analytics currently)
     *
     * @return Collection
     */
    public function execute();
}
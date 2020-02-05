<?php namespace Common\Admin\Analytics;

use Cache;
use Carbon\Carbon;
use Common\Core\Controller;
use Common\Admin\Analytics\Actions\GetAnalyticsData;
use Common\Admin\Analytics\Actions\GetAnalyticsHeaderDataAction;
use Exception;

class AnalyticsController extends Controller
{
    /**
     * @var GetAnalyticsData
     */
    private $getDataAction;

    /**
     * @var GetAnalyticsHeaderDataAction
     */
    private $getHeaderDataAction;

    /**
     * @param GetAnalyticsData $getDataAction
     * @param GetAnalyticsHeaderDataAction $getHeaderDataAction
     */
    public function __construct(
        GetAnalyticsData $getDataAction,
        GetAnalyticsHeaderDataAction $getHeaderDataAction
    )
    {
        $this->getDataAction = $getDataAction;
        $this->getHeaderDataAction = $getHeaderDataAction;
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function stats()
    {
        $this->authorize('index', 'ReportPolicy');

        $mainData = $data = Cache::remember('analytics.data.main', Carbon::now()->addDay(), function() {
            return $this->getMainData();
        }) ?: [];

        $headerData = $data = Cache::remember('analytics.data.header', Carbon::now()->addDay(), function() {
            return $this->getHeaderDataAction->execute();
        });

        return $this->success([
            'mainData' => $mainData,
            'headerData' => $headerData,
        ]);
    }

    private function getMainData() {
        try {
            return $this->getDataAction->execute();
        } catch (Exception $e) {
            return null;
        }
    }
}

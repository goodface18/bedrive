<?php namespace Common\Core\Controllers;

use Common\Core\BootstrapData;
use Common\Core\Controller;

class BootstrapController extends Controller
{
    /**
     * Get data needed to bootstrap the application.
     *
     * @param BootstrapData $bootstrapData
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBootstrapData(BootstrapData $bootstrapData)
    {
        return response(['data' => $bootstrapData->get()]);
    }
}

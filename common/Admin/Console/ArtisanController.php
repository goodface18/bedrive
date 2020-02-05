<?php

namespace Common\Admin\Console;

use Artisan;
use Common\Core\Controller;
use Common\Settings\Setting;
use Illuminate\Http\Request;

class ArtisanController extends Controller
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function call()
    {
        $this->authorize('update', Setting::class);

        $commandName = $this->request->get('command');
        $params = $this->request->get('params', []);

        Artisan::call($commandName, $params);

        return $this->success();
    }
}
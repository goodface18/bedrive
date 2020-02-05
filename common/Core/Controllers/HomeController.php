<?php namespace Common\Core\Controllers;

use Common\Core\BootstrapData;
use Common\Core\Controller;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Common\Settings\Settings;

class HomeController extends Controller {

    /**
     * @var BootstrapData
     */
    private $bootstrapData;

    /**
     * @var Settings
     */
    private $settings;

    /**
     * @param BootstrapData $bootstrapData
     * @param Settings $settings
     */
    public function __construct(BootstrapData $bootstrapData, Settings $settings)
    {
        $this->bootstrapData = $bootstrapData;
        $this->settings = $settings;
    }

    /**
	 * @return View|Response
	 */
	public function show()
	{
        $htmlBaseUri = '/';

        //get uri for html "base" tag
        if (substr_count(url(''), '/') > 2) {
            $htmlBaseUri = parse_url(url(''))['path'] . '/';
        }

        if ($response = $this->handleSeo()) {
            return $response;
        }

        return response(view('app')
            ->with('bootstrapData', $this->bootstrapData->get())
            ->with('htmlBaseUri', $htmlBaseUri)
            ->with('settings', $this->settings));
	}
}

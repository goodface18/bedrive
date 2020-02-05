<?php namespace Common\Admin\Appearance\Controllers;

use Common\Admin\Appearance\AppearanceSaver;
use Common\Admin\Appearance\AppearanceValues;
use Common\Settings\Settings;
use Illuminate\Http\Request;
use Common\Core\Controller;

class AppearanceController extends Controller {

    /**
     * @var Request
     */
    private $request;

    /**
     * @var AppearanceValues
     */
    private $values;

    /**
     * @var AppearanceSaver
     */
    private $saver;

    /**
     * @var Settings
     */
    private $settings;

    /**
     * @param Request $request
     * @param AppearanceValues $values
     * @param AppearanceSaver $saver
     * @param Settings $settings
     */
    public function __construct(
        Request $request,
        AppearanceValues $values,
        AppearanceSaver $saver,
        Settings $settings
    )
	{
        $this->saver = $saver;
        $this->values = $values;
        $this->request = $request;
        $this->settings = $settings;
    }

    /**
     * Save user modifications to site appearance.
     */
    public function save()
    {
        $this->authorize('update', 'AppearancePolicy');

        $this->saver->save($this->request->all());
        return $this->success();
	}

    /**
     * Get user defined and default values for appearance editor.
     *
     * @return array
     */
    public function getValues()
    {
        $this->authorize('update', 'AppearancePolicy');

        return $this->values->get();
    }
}

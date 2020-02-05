<?php namespace App\Http\Controllers;

use Common\Core\Controller;
use Common\Settings\DotEnvEditor;
use Common\Settings\Setting;
use DB;
use Auth;
use Cache;
use Artisan;
use Exception;
use Schema;

class UpdateController extends Controller {
    /**
     * @var DotEnvEditor
     */
    private $dotEnvEditor;

    /**
     * @var Setting
     */
    private $setting;

    /**
     * @param DotEnvEditor $dotEnvEditor
     * @param Setting $setting
     */
	public function __construct(DotEnvEditor $dotEnvEditor, Setting $setting)
	{
        $this->setting = $setting;
        $this->dotEnvEditor = $dotEnvEditor;

        if ( ! config('common.site.disable_update_auth') && version_compare(config('common.site.version'), $this->getAppVersion()) === 0) {
            if ( ! Auth::check() || ! Auth::user()->hasPermission('admin')) {
                abort(403);
            }
        }
    }

    /**
     * Show update view.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show()
    {
        return view('update');
    }

    /**
     * Perform the update.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update()
	{
        //fix "index is too long" issue on MariaDB and older mysql versions
        Schema::defaultStringLength(191);

        Artisan::call('migrate', ['--force' => 'true']);
        Artisan::call('db:seed', ['--force' => 'true']);
        Artisan::call('common:seed');

        $version = $this->getAppVersion();
        $this->dotEnvEditor->write(['app_version' => $version]);

        Cache::flush();

        return redirect()->back()->with('status', 'Updated the site successfully.');
	}

    /**
     * Get new app version.
     *
     * @return string
     */
    private function getAppVersion()
    {
        try {
            return $this->dotEnvEditor->load(base_path('.env.example'))['app_version'];
        } catch (Exception $e) {
            return '2.1.2';
        }
    }
}
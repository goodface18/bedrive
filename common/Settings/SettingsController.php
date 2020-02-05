<?php namespace Common\Settings;

use File;
use Artisan;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Common\Core\Controller;

class SettingsController extends Controller {

    /**
     * Settings service instance.
     *
     * @var Settings;
     */
    private $settings;

    /**
     * Laravel Request instance.
     *
     * @var Request;
     */
    private $request;

    /**
     * @var DotEnvEditor
     */
    private $dotEnv;

    /**
     * @param Request $request
     * @param Settings $settings
     * @param DotEnvEditor $dotEnv
     */
    public function __construct(Request $request, Settings $settings, DotEnvEditor $dotEnv)
    {
        $this->request  = $request;
        $this->settings = $settings;
        $this->dotEnv = $dotEnv;
    }

    /**
     * Get all application settings.
     *
     * @return array
     */
    public function index()
    {
        $this->authorize('index', Setting::class);

        return ['server' => $this->dotEnv->load(), 'client' => $this->settings->all(true)];
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function persist()
    {
        $this->authorize('update', Setting::class);

        $clientSettings = json_decode(base64_decode($this->request->get('client')), true);
        $serverSettings = json_decode(base64_decode($this->request->get('server')), true);

        // need to handle files before validating
        // TODO: maybe refactor this, if need to handle
        // something else besides google analytics certificate
        $this->handleFiles();

        if ($errResponse = $this->validateSettings($serverSettings, $clientSettings)) {
            return $errResponse;
        }

        if ($serverSettings) {
            $this->dotEnv->write($serverSettings);
        }

        if ($clientSettings) {
            $this->settings->save($clientSettings);
        }

        Artisan::call('cache:clear');

        return $this->success();
    }

    private function handleFiles()
    {
        $files = $this->request->file('files');

        // store google analytics certificate file
        if ($certificateFile = Arr::get($files, 'certificate')) {
            File::put(storage_path('laravel-analytics/certificate.p12'), file_get_contents($certificateFile));
        }
    }

    /**
     * @param array $serverSettings
     * @param array $clientSettings
     * @return \Illuminate\Http\JsonResponse
     */
    private function validateSettings($serverSettings, $clientSettings)
    {
        // flatten "client" and "server" arrays into single array
        $values = array_merge(
            $serverSettings ?: [],
            $clientSettings ?: [],
            $this->request->file('files', [])
        );
        $keys = array_keys($values);
        $validators = config('common.setting-validators');

        foreach ($validators as $validator) {
            if (empty(array_intersect($validator::KEYS, $keys))) continue;

            if ($messages = app($validator)->fails($values)) {
                return $this->error($messages);
            }
        }
    }
}

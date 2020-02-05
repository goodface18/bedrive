<?php namespace Common\Admin\Appearance;

use Common\Settings\DotEnvEditor;
use Common\Settings\Settings;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemManager;

class AppearanceSaver
{
    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * Path to custom css theme.
     */
    const THEME_PATH = 'appearance/theme.css';

    /**
     * Path to stored user selected values for css theme.
     */
    const THEME_VALUES_PATH = 'appearance/theme-values.json';

    const CUSTOM_CSS_PATH = 'custom-code/custom-styles.css';
    const CUSTOM_JS_PATH = 'custom-code/custom-scripts.js';

    /**
     * Local filesystem instance.
     *
     * @var Settings
     */
    private $settings;

    /**
     * Flysystem Instance.
     *
     * @var FilesystemManager
     */
    private $storage;

    /**
     * @var DotEnvEditor
     */
    private $envEditor;

    /**
     * AppearanceManager constructor.
     *
     * @param Filesystem $fs
     * @param Settings $settings
     * @param FilesystemManager $storage
     * @param DotEnvEditor $envEditor
     */
    public function __construct(
        Filesystem $fs,
        Settings $settings,
        FilesystemManager $storage,
        DotEnvEditor $envEditor
    )
    {
        $this->fs = $fs;
        $this->settings = $settings;
        $this->storage = $storage;
        $this->envEditor = $envEditor;
    }

    /**
     * Save user modifications to site appearance.
     *
     * @param array $params
     */
    public function save($params)
    {
        foreach ($params as $key => $value) {
            if ($key === 'colors') {
                $this->saveTheme($value);
            } else if (starts_with($key, 'env.')) {
                $this->saveEnvSetting($key, $value);
            } else if ($key === 'custom_code.css' || $key === 'custom_code.js') {
                $this->saveCustomCode($key, $value);
            } else if (is_string($value) && str_contains($value, 'branding-images')) {
                $this->saveImageSetting($key, $value);
            } else {
                $this->saveBasicSetting($key, $value);
            }
        }
    }

    /**
     * Delete old image and store new one for specified setting.
     *
     * @param string $key
     * @param string $value
     */
    private function saveImageSetting($key, $value)
    {
        $old = $this->settings->get($key);

        //delete old file for this image, if it exists
        $this->storage->disk('public')->delete($old);

        //store new image file path in database
        $this->saveBasicSetting($key, $value);
    }

    /**
     * Save specified setting into .env file.
     *
     * @param string $key
     * @param string $value
     */
    private function saveEnvSetting($key, $value)
    {
        $key = str_replace('env.', '', $key);

        $this->envEditor->write([
            $key => $value
        ]);
    }

    /**
     * Save basic setting to database or .env file.
     *
     * @param string $key
     * @param mixed $value
     */
    private function saveBasicSetting($key, $value)
    {
        $value = is_array($value) ? json_encode($value) : $value;

        if ($this->settings->get($key) !== $value) {
            $this->settings->save([$key => $value]);
        }
    }

    /**
     * Save generated CSS theme and user defined theme values to disk.
     *
     * @param array $params
     */
    private function saveTheme($params)
    {
        $this->storage->disk('public')->put(self::THEME_VALUES_PATH, json_encode($params['themeValues']));
        $this->storage->disk('public')->put(self::THEME_PATH, $params['theme']);
    }

    public function saveCustomCode($key, $value)
    {
        if ($key === 'custom_code.css') {
            $path = self::CUSTOM_CSS_PATH;
            $loadKey = 'custom_code.load_css';
        } else {
            $path = self::CUSTOM_JS_PATH;
            $loadKey = 'custom_code.load_js';
        }

        //make sure to update load setting for css and js, so we don't load empty files
        $this->settings->save([$loadKey => !!$value]);

        return $this->storage->disk('public')->put($path, $value);
    }
}
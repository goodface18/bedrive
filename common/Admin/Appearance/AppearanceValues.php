<?php namespace Common\Admin\Appearance;

use Common\Core\Prerender\MetaTags;
use Common\Settings\Settings;
use GuzzleHttp\Client;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Arr;

class AppearanceValues
{
    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @var FilesystemManager
     */
    private $storage;

    /**
     * Path to custom css theme.
     */
    const THEME_PATH = 'resources/editable-theme.css';

    /**
     * Path to stored user selected values for css theme.
     */
    const THEME_VALUES_PATH = 'appearance/theme-values.json';

    /**
     * ENV values to include.
     */
    const ENV_KEYS = ['app_url', 'app_name'];

    /**
     * @var Client
     */
    private $http;

    /**
     * @var Settings
     */
    private $settings;

    /**
     * AppearanceManager constructor.
     *
     * @param Filesystem $fs
     * @param FilesystemManager $storage
     * @param Client $http
     * @param Settings $settings
     */
    public function __construct(
        Filesystem $fs,
        FilesystemManager $storage,
        Client $http,
        Settings $settings
    )
    {
        $this->fs = $fs;
        $this->storage = $storage;
        $this->http = $http;
        $this->settings = $settings;
    }

    /**
     * Get user defined and default values for appearance editor.
     *
     * @return array
     */
    public function get()
    {
        //get default settings for the application
        $settings = config('common.default-settings');

        list($theme, $variables) = $this->getCssThemeAndVariables();

        //merge default theme values with user selected values
        if ($this->storage->disk('public')->exists(self::THEME_VALUES_PATH)) {
            $variables = array_replace_recursive(
                $variables,
                json_decode($this->storage->disk('public')->get(self::THEME_VALUES_PATH), true)
            );
        }

        //add css theme to settings array
        $settings[] = ['name' => 'editable_theme', 'value' => $theme];

        //add env settings
        $env = [];
        foreach (self::ENV_KEYS as $key) {
            $env['env.'.$key] = config(str_replace('_', '.', $key));
        }
        $settings[] = ['name' => 'env', 'value' => $env];

        $settings[] = ['name' => 'colors', 'value' => $variables];

        //add custom code
        $settings[] = ['name' => 'custom_code.css', 'value' => $this->getCustomCodeValue(AppearanceSaver::CUSTOM_CSS_PATH)];
        $settings[] = ['name' => 'custom_code.js', 'value' => $this->getCustomCodeValue(AppearanceSaver::CUSTOM_JS_PATH)];

        //add seo fields
        $seoFields = ['name' => 'seo_fields', 'value' => $this->prepareSeoValues()];

        foreach ($settings as $key => $setting) {
            if (str_contains($setting['name'], 'seo.')) {
                $seoFields['value'][] = $setting;
                unset($settings[$key]);
            }
        }

        array_push($settings, $seoFields);

        return array_values($settings);
    }

    /**
     * Prepare seo values for appearance editor.
     *
     * @return array
     */
    private function prepareSeoValues()
    {
        $flat = [];
        $seoConfig = config('seo');

        if ( ! $seoConfig) return [];

        $seo = array_except($seoConfig, 'common');

        // resource groups meta tags for artist, movie, track etc.
        foreach ($seo as $resourceName => $resource) {
            // resource has config for each verb (show, index etc.)
            foreach ($resource as $verbName => $verb) {
                // verb has a list of meta tags (og:title, description etc.)
                foreach ($verb as $metaTag) {
                    $property = Arr::get($metaTag, 'property');
                    if (array_search($property, MetaTags::EDITABLE_TAGS) === false) continue;

                    $name = str_replace('og:', '', "$resourceName > $verbName > $property");
                    $name = str_replace('-', ' ', $name);

                    $key = "seo.$resourceName.$verbName.$property";
                    $defaultValue = $metaTag['content'];

                    $flat[] = [
                        'name' => $name,
                        'key' => $key,
                        'value' => $this->settings->get($key, $defaultValue),
                        'defaultValue' => $defaultValue,
                        'input_type' => 'textarea'
                    ];
                }
            }
        }

        return $flat;
    }

    /**
     * Get css theme and default variables for appearance editor.
     *
     * @return array
     */
    private function getCssThemeAndVariables()
    {
        $theme = $this->fs->get(base_path(self::THEME_PATH));

        //capture and remove css variables defined in :root
        preg_match('/:root {(.+?)}/s', $theme, $matches);
        $theme = trim(preg_replace('/:root {(.+?)}/s', '', $theme));

        //transform css variables into dot notation keys
        $theme = preg_replace_callback('/var\(--(.+?)\)/', function ($matches) {
            return str_replace('-', '.', $matches[1]);
        }, $theme);

        $lines = explode(PHP_EOL, trim($matches[1]));

        //transform css variables into key => value pairs
        $variables = array_map(function ($line) {
            $pair = explode(':', $line);
            $key = trim(str_replace(['--', '-'], ['', '.'], $pair[0]));
            $value = str_replace(';', '', trim($pair[1]));
            return ['name' => $key, 'display_name' => $this->makeColorDisplayName($key), 'value' => $value];
        }, $lines);

        return [$theme, $variables];
    }

    /**
     * Get display name for specified color key.
     * "site.colors.bg-100" to "Background Color 100"
     *
     * @param $key
     * @return null|string|string[]
     */
    private function makeColorDisplayName($key) {
        $key = preg_replace("/([a-z]+).text.color$/", "$1 Text Color", $key);
        $key = preg_replace("/([a-z]+).text.color.([0-9]+)/", "Text Color $2", $key);
        $key = preg_replace("/([a-z]+).border.color.([0-9]+)/", "Border Color $2", $key);
        $key = preg_replace("/([a-z\.]+).bg.color$/", "$1 Background Color", $key);
        $key = preg_replace("/([a-z]+).bg.color.([0-9]+)/", "Background Color $2", $key);
        $key = preg_replace("/([a-z]+).accent.color/", "Accent Color", $key);
        $key = preg_replace("/([a-z]+).primary.color.([0-9]+)/", "Primary Color $2", $key);
        $key = strtolower($key);
        $key = str_replace('site', '', $key);
        $key = str_replace('.', ' ', $key);
        return ucwords(trim($key));
    }

    private function getCustomCodeValue($path)
    {
        try {
            return $this->storage->disk('public')->get($path);
        } catch (\Exception $e) {
            return '';
        }
    }
}
<?php namespace Common\Database\Seeds;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Common\Settings\Setting;
use Illuminate\Database\Seeder;

class SettingsTableSeeder extends Seeder
{
    /**
     * @var Setting
     */
    private $setting;

    /**
     * SettingsTableSeeder constructor.
     *
     * @param Setting $setting
     */
    public function __construct(Setting $setting)
    {
        $this->setting = $setting;
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $defaultSettings = config('common.default-settings');

        $names = [];

        $defaultSettings = array_map(function($setting) use(&$names) {
            $names[] = $setting['name'];

            $setting['created_at'] = Carbon::now();
            $setting['updated_at'] = Carbon::now();

            //make sure all settings have "private" field to
            //avoid db errors due to different column count
            if ( ! array_key_exists('private', $setting)) {
                $setting['private'] = 0;
            }

            return $setting;
        }, $defaultSettings);

        $existing = $this->setting->whereIn('name', $names)->pluck('name');

        //only insert settings that don't already exist in database
        $new = array_filter($defaultSettings, function($setting) use($existing) {
            return ! $existing->contains($setting['name']);
        });

        $this->setting->insert($new);

        $this->mergeMenusSetting($defaultSettings);
    }

    /**
     * Merge existing menus setting json with new one.
     *
     * @param array $defaultSettings
     */
    private function mergeMenusSetting($defaultSettings)
    {
        $existing = json_decode($this->setting->where('name', 'menus')->first()->value, true);

        $new = json_decode(Arr::first($defaultSettings, function($value) {
            return $value['name'] === 'menus';
        })['value'], true);

        foreach ($new as $menu) {
            $alreadyHas = Arr::first($existing, function($value) use($menu) {
                return $value['name'] === $menu['name'];
            });

            if ( ! $alreadyHas) {
                $existing[] = $menu;
            }
        }

        $this->setting->where('name', 'menus')->update(['value' => json_encode($existing)]);
    }
}

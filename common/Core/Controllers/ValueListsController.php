<?php namespace Common\Core\Controllers;

use Illuminate\Support\Str;
use Common\Core\Controller;
use Illuminate\Filesystem\Filesystem;
use Common\Localizations\Localization;

class ValueListsController extends Controller
{
    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @var Localization
     */
    private $localization;

    /**
     * @param Filesystem $fs
     * @param Localization $localization
     */
    public function __construct(Filesystem $fs, Localization $localization)
    {
        $this->fs = $fs;
        $this->localization = $localization;
    }

    /**
     * Get specified select option lists.
     *
     * @param string $names
     * @return \Illuminate\Http\JsonResponse
     */
    public function get($names)
    {
        $options = collect(explode(',', $names))
            ->mapWithKeys(function($name) {
                $methodName = Str::studly($name);
                $value = method_exists($this, $name) ?
                    $this->$methodName() :
                    null;
                return [$name => $value];
            })->filter();

        return $this->success($options);
    }

    public function permissions()
    {
        $this->authorize('index', 'PermissionPolicy');

        $permissions = config('common.permissions.all');

        // format legacy permissions into ['name' => 'permission] array
        foreach ($permissions as $groupName => $group) {
            $permissions[$groupName] = array_map(function($permission) {
                if (is_array($permission)) return $permission;
                return ['name' => $permission];
            }, $group);
        }

        // remove billing permissions, if billing functionality is disabled
        if (isset($permissions['billing_plans']) && ! config('common.site.billing_enabled')) {
            unset($permissions['billing_plans']);
        }

        return $permissions;
    }

    public function currencies()
    {
        return json_decode($this->fs->get(__DIR__ . '/../../resources/lists/currencies.json'), true);
    }

    public function timezones()
    {
        return json_decode($this->fs->get(__DIR__ . '/../../resources/lists/timezones.json'), true);
    }

    public function countries()
    {
        return json_decode($this->fs->get(__DIR__ . '/../../resources/lists/countries.json'), true);
    }

    public function languages()
    {
        return json_decode($this->fs->get(__DIR__ . '/../../resources/lists/languages.json'), true);
    }

    public function localizations()
    {
        return $this->localization->get(['name'])->pluck('name')->toArray();
    }
}

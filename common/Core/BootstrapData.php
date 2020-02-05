<?php namespace Common\Core;

use App;
use Common\Auth\Roles\Role;
use Illuminate\Http\Request;
use Common\Localizations\LocalizationsRepository;
use Common\Settings\Settings;
use Common\Localizations\Localization;

class BootstrapData
{
    /**
     * @var Settings
     */
    private $settings;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Localization
     */
    private $localizationRepository;

    /**
     * @var Role
     */
    private $role;

    /**
     * @param Settings $settings
     * @param Request $request
     * @param Role $role
     * @param LocalizationsRepository $localizationsRepository
     */
    public function __construct(
        Settings $settings,
        Request $request,
        Role $role,
        LocalizationsRepository $localizationsRepository
    )
    {
        $this->role = $role;
        $this->request = $request;
        $this->settings = $settings;
        $this->localizationRepository = $localizationsRepository;
    }

    /**
     * Get data needed to bootstrap the application.
     *
     * @return string
     */
    public function get()
    {
        $bootstrap = [];
        $bootstrap['settings'] = $this->settings->all();
        $bootstrap['settings']['base_url'] = url('');
        $bootstrap['settings']['version'] = config('common.site.version');
        $bootstrap['csrf_token'] = csrf_token();
        $bootstrap['guests_role'] = $this->role->where('guests', 1)->first();
        $bootstrap['i18n'] = $this->getLocalizationsData() ?: null;
        $bootstrap['user'] = $this->getCurrentUser();

        //get extra bootstrap data provided by application
        if ($namespace = config('common.site.extra_bootstrap_data')) {
            $bootstrap = App::make($namespace)->get($bootstrap);
        }

        if ($bootstrap['user']) {
            $bootstrap['user'] = $bootstrap['user']->toArray();
        }

        return base64_encode(json_encode($bootstrap));
    }

    /**
     * Load current user and his roles.
     */
    private function getCurrentUser()
    {
        $user = $this->request->user();

        if ($user) {
            // load user subscriptions, if billing is enabled
            if (app(Settings::class)->get('billing.enable') && ! $user->relationLoaded('subscriptions')) {
                $user->load('subscriptions.plan');
            }

            // load user roles, if not already loaded
            if (! $user->relationLoaded('roles')) {
                $user->load('roles');
            }
        }

        return $user;
    }

    /**
     * Get currently selected i18n language.
     *
     * @return Localization
     */
    private function getLocalizationsData()
    {
        if ( ! $this->settings->get('i18n.enable')) return null;

        //get user selected or default language
        $userLang = $this->request->user() ? $this->request->user()->language : null;

        if ( ! $userLang) {
            $userLang = config('app.locale');
        }

        if ($userLang) {
            return $this->localizationRepository->getByName($userLang);
        }
    }
}

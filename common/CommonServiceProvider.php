<?php

namespace Common;

use Common\Admin\Analytics\AnalyticsServiceProvider;
use Common\Admin\Appearance\Commands\GenerateCssTheme;
use Common\Billing\BillingPlan;
use Common\Billing\Subscription;
use Common\Billing\SyncBillingPlansCommand;
use Common\Core\Contracts\AppUrlGenerator;
use Common\Core\Middleware\RestrictDemoSiteFunctionality;
use Common\Core\Commands\SeedCommand;
use Common\Core\Policies\AppearancePolicy;
use Common\Core\Policies\FileEntryPolicy;
use Common\Core\Policies\LocalizationPolicy;
use Common\Core\Policies\MailTemplatePolicy;
use Common\Core\Policies\PagePolicy;
use Common\Core\Policies\PermissionPolicy;
use Common\Core\Policies\RolePolicy;
use Common\Core\Policies\SettingPolicy;
use Common\Core\Policies\SubscriptionPolicy;
use Common\Core\Policies\UserPolicy;
use Common\Core\Prerender\BaseUrlGenerator;
use Common\Files\Providers\BackblazeServiceProvider;
use Common\Files\Providers\DigitalOceanServiceProvider;
use Common\Files\Providers\DropboxServiceProvider;
use Common\Localizations\Commands\ExportTranslations;
use Common\Mail\MailTemplate;
use Common\Core\Policies\BillingPlanPolicy;
use Common\Core\Policies\ReportPolicy;
use Common\Auth\BaseUser;
use Gate;
use Illuminate\Support\Collection;
use Validator;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\SocialiteServiceProvider;
use Common\Files\FileEntry;
use Common\Auth\Roles\Role;
use Common\Localizations\Localization;
use Common\Pages\Page;
use Common\Settings\Setting;

class CommonServiceProvider extends ServiceProvider
{
    const CONFIG_FILES = ['permissions', 'default-settings', 'site', 'demo', 'mail-templates', 'setting-validators'];

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/routes.php');
        $this->loadMigrationsFrom(__DIR__.'/Database/migrations');
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'common');

        $this->registerPolicies();
        $this->registerCustomValidators();
        $this->registerCommands();
        $this->registerMiddleware();
        $this->registerCollectionExtensions();

        $configs = collect(self::CONFIG_FILES)->mapWithKeys(function($file) {
            return [__DIR__."/resources/config/$file.php" => config_path("common/$file.php")];
        })->toArray();

        $this->publishes($configs);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $loader = \Illuminate\Foundation\AliasLoader::getInstance();

        // register socialite service provider and alias
        $this->app->register(SocialiteServiceProvider::class);
        $this->app->register(AnalyticsServiceProvider::class);
        $loader->alias('Socialite', Socialite::class);

        // url generator for SEO
        $this->app->bind(
            AppUrlGenerator::class,
            BaseUrlGenerator::class
        );

        $this->registerDevProviders();

        $this->deepMergeDefaultSettings(__DIR__ . "/resources/config/default-settings.php", "common.default-settings");
        $this->deepMergeConfigFrom(__DIR__ . "/resources/config/demo-blocked-routes.php", "common.demo-blocked-routes");
        $this->deepMergeConfigFrom(__DIR__ . "/resources/config/permissions.php", "common.permissions");
        $this->deepMergeConfigFrom(__DIR__ . "/resources/config/mail-templates.php", "common.mail-templates");
        $this->mergeConfigFrom(__DIR__ . "/resources/config/site.php", "common.site");
        $this->mergeConfigFrom(__DIR__ . "/resources/config/setting-validators.php", "common.setting-validators");

        // register flysystem providers
        if (config('common.site.uploads_disk') === 'uploads_dropbox') {
            $this->app->register(DropboxServiceProvider::class);
        } else if (config('common.site.uploads_disk') === 'uploads_digitalocean') {
            $this->app->register(DigitalOceanServiceProvider::class);
        } else if (config('common.site.uploads_disk') === 'uploads_backblaze') {
            $this->app->register(BackblazeServiceProvider::class);
        }
    }

    /**
     * Register package middleware.
     */
    private function registerMiddleware()
    {
        if ($this->app['config']->get('common.site.demo')) {
            $this->app['router']->pushMiddlewareToGroup('web', RestrictDemoSiteFunctionality::class);
        }
    }

    /**
     * Register custom validation rules with laravel.
     */
    private function registerCustomValidators()
    {
        Validator::extend('hash', 'Common\Auth\Validators\HashValidator@validate');
        Validator::extend('email_confirmed', 'Common\Auth\Validators\EmailConfirmedValidator@validate');
    }

    /**
     * Deep merge the given configuration with the existing configuration.
     *
     * @param  string  $path
     * @param  string  $key
     * @return void
     */
    private function deepMergeConfigFrom($path, $key)
    {
        $config = $this->app['config']->get($key, []);
        $this->app['config']->set($key, array_merge_recursive(require $path, $config));
    }

    private function registerPolicies()
    {
        Gate::policy('App\Model', 'App\Policies\ModelPolicy');
        Gate::policy(FileEntry::class, FileEntryPolicy::class);
        Gate::policy(BaseUser::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Page::class, PagePolicy::class);
        Gate::policy('PermissionPolicy', PermissionPolicy::class);
        Gate::policy(Setting::class, SettingPolicy::class);
        Gate::policy(Localization::class, LocalizationPolicy::class);
        Gate::policy('AppearancePolicy', AppearancePolicy::class);
        Gate::policy('ReportPolicy', ReportPolicy::class);
        Gate::policy(MailTemplate::class, MailTemplatePolicy::class);
        Gate::policy(BillingPlan::class, BillingPlanPolicy::class);
        Gate::policy(Subscription::class, SubscriptionPolicy::class);
    }

    private function registerCommands()
    {
        $this->commands([
            GenerateCssTheme::class,
            ExportTranslations::class,
            SeedCommand::class,
            SyncBillingPlansCommand::class,
        ]);
    }

    /**
     * Deep merge "default-settings" config values.
     *
     * @param string $path
     * @param $configKey
     * @return void
     */
    private function deepMergeDefaultSettings($path, $configKey)
    {
        $defaultSettings = require $path;
        $userSettings = $this->app['config']->get($configKey, []);

        foreach ($userSettings as $userSetting) {
            //remove default setting, if it's overwritten by user setting
            foreach ($defaultSettings as $key => $defaultSetting) {
                if ($defaultSetting['name'] === $userSetting['name']) {
                    unset($defaultSettings[$key]);
                }
            }

            //push user setting into default settings array
            $defaultSettings[] = $userSetting;
        }

        $this->app['config']->set($configKey, $defaultSettings);
    }

    private function registerDevProviders()
    {
        if ($this->app->environment() === 'production') return;

        if ($this->ideHelperExists()) {
            $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
        }

        if ($this->clockworkExists()) {
            $this->app->register(\Clockwork\Support\Laravel\ClockworkServiceProvider::class);
        }
    }

    private function clockworkExists() {
        return class_exists(\Clockwork\Support\Laravel\ClockworkServiceProvider::class);
    }

    private function ideHelperExists() {
        return class_exists(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
    }

    private function registerCollectionExtensions()
    {
        // convert all array items to lowercase
        Collection::macro('toLower', function ($key = null) {
            return $this->map(function ($value) use($key) {
                // remove all whitespace and lowercase
                if (is_string($value)) {
                    return str_slug($value, ' ');
                } else {
                    $value[$key] = str_slug($value[$key], ' ');
                    return $value;
                }
            });
        });
    }
}

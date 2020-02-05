<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Admin\GetAnalyticsHeaderData;
use Illuminate\Database\Eloquent\Relations\Relation;
use Common\Admin\Analytics\Actions\GetAnalyticsHeaderDataAction;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //make sure tags relation is loaded properly
        Relation::morphMap([
            'Common\Files\FileEntry' => 'App\FileEntry',
        ]);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(
            GetAnalyticsHeaderDataAction::class,
            GetAnalyticsHeaderData::class
        );
    }
}

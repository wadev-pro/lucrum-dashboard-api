<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\DashboardService;

class DashboardServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->singleton('DashboardService', function($app) {
            return new DashboardService();
        });
    }
}

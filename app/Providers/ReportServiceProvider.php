<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\ReportsService;

class ReportServiceProvider extends ServiceProvider
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
        $this->app->singleton('ReportsService', function($app) {
            return new ReportsService();
        });
    }
}

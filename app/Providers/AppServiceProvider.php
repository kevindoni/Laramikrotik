<?php

namespace App\Providers;

use App\Http\Controllers\CompanySettingsController;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrap();
        
        // Share company settings with all views
        View::composer('layouts.admin', function ($view) {
            $companySettings = CompanySettingsController::getSettings();
            $view->with('companySettings', $companySettings);
        });
    }
}

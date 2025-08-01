<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Helpers\Nav;

class HelperServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register Nav as a global class alias
        if (!class_exists('Nav')) {
            class_alias(Nav::class, 'Nav');
        }
    }
}

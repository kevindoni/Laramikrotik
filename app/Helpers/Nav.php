<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Request;

class Nav
{
    /**
     * Check if current route is active
     *
     * @param string $route
     * @return string
     */
    public static function isRoute($route)
    {
        return Route::currentRouteName() === $route ? 'active' : '';
    }

    /**
     * Check if current route starts with given pattern
     *
     * @param string $pattern
     * @return string
     */
    public static function isRoutePattern($pattern)
    {
        return str_starts_with(Route::currentRouteName(), $pattern) ? 'active' : '';
    }

    /**
     * Check if current URL matches given pattern
     *
     * @param string $pattern
     * @return string
     */
    public static function isUrl($pattern)
    {
        return Request::is($pattern) ? 'active' : '';
    }
}

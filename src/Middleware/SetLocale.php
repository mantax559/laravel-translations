<?php

namespace Mantax559\LaravelTranslations\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Session::has('locale') && in_array(Session::get('locale'), array_keys(config('laravel-translations.locales')))) {
            App::setLocale(Session::get('locale'));
        } elseif (config('laravel-translations.primary_locale')) {
            App::setLocale(config('laravel-translations.primary_locale'));
        } else {
            App::setLocale(config('laravel-translations.fallback_locale'));
        }

        return $next($request);
    }
}

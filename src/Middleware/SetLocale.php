<?php

namespace Mantax559\LaravelTranslations\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Mantax559\LaravelTranslations\Helpers\TranslationHelper;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Session::has('locale') && in_array(Session::get('locale'), TranslationHelper::getLocales())) {
            App::setLocale(Session::get('locale'));
        } elseif (in_array(config('app.locale'), TranslationHelper::getLocales())) {
            App::setLocale(config('app.locale'));
        } else {
            App::setLocale(config('app.fallback_locale'));
        }

        return $next($request);
    }
}

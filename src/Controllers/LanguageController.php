<?php

namespace Mantax559\LaravelTranslations\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Session;

class LanguageController
{
    public function change(string $locale): RedirectResponse
    {
        $locale = format_string($locale, [3, 7, 8]);

        if (in_array($locale, array_keys(config('laravel-translations.locales')))) {
            Session::put('locale', $locale);
        }

        return back();
    }
}

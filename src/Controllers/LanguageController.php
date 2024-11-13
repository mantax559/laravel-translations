<?php

namespace Mantax559\LaravelTranslations\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Session;
use Mantax559\LaravelTranslations\Helpers\TranslationHelper;

class LanguageController
{
    public function change(string $locale): RedirectResponse
    {
        $locale = format_string($locale, [3, 7, 8]);

        if (in_array($locale, TranslationHelper::getLocales())) {
            Session::put('locale', $locale);
        }

        return back();
    }
}

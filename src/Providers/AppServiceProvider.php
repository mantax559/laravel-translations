<?php

namespace Mantax559\LaravelTranslations\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    private const PATH_CONFIG = __DIR__.'/../../config/laravel-translations.php';

    public function boot(): void
    {
        $this->publishes([
            self::PATH_CONFIG => config_path('laravel-translations.php'),
        ], 'config');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(self::PATH_CONFIG, 'laravel-translations');
    }
}

![GitHub release (latest by date)](https://img.shields.io/github/v/release/mantax559/laravel-translations?label=latest&style=flat-square)
![GitHub release (latest SemVer including pre-releases)](https://img.shields.io/github/v/release/mantax559/laravel-translations?include_prereleases&label=pre-release&style=flat-square)
![Packagist](https://img.shields.io/packagist/l/mantax559/laravel-translations?style=flat-square)
![PHP from Packagist](https://img.shields.io/packagist/php-v/mantax559/laravel-translations?style=flat-square)
# Laravel Translations
## Installation & Setup
You can install the package via composer:

    composer require mantax559/laravel-translations

The package will automatically register its service provider.

## Customisation

### Config

You can optionally publish the config file with:

    php artisan vendor:publish --provider="Mantax559\LaravelTranslations\Providers\AppServiceProvider" --tag=config

### Routes

To allow the user to change the language, add this route:

    Route::get('language/{language}', [\Mantax559\LaravelTranslations\Controllers\LanguageController, 'change'])->name('language.change');

### Tests
You can run tests with the command:

    vendor/bin/phpunit

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

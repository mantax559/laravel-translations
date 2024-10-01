<?php

namespace Mantax559\LaravelTranslations\Helpers;

use Mantax559\LaravelHelpers\Helpers\ValidationHelper;
use Mantax559\LaravelTranslations\Enums\TranslationStatusEnum;
use Mantax559\LaravelTranslations\Exceptions\LocaleNotDefinedException;

class TranslationHelper
{
    public static function getValidationRules(string $translationName, array $textColumns = [], array $stringColumns = [], bool $withTranslationStatus = false): array
    {
        $rules = [];

        foreach (array_keys(config('laravel-translations.locales')) as $locale) {
            $localeRules = [];

            if ($withTranslationStatus) {
                $localeRules["$translationName.$locale.".config('laravel-translations.translation_status_column')] = ValidationHelper::getEnumRules(enum: TranslationStatusEnum::class);
            }

            $requiredFields = [];

            foreach ($textColumns as $textColumn) {
                $requiredFields[] = "$locale.$textColumn";
                $localeRules["$translationName.$locale.$textColumn"] = ValidationHelper::getTextRules(required: false);
            }

            foreach ($stringColumns as $stringColumn) {
                $requiredFields[] = "$locale.$stringColumn";
                $localeRules["$translationName.$locale.$stringColumn"] = ValidationHelper::getStringRules(required: false);
            }

            $rulesCount = count($localeRules);
            $localeRules["$translationName.$locale"] = ValidationHelper::getArrayRules(size: $rulesCount);

            $rules = array_merge($rules, $localeRules);
        }

        $rulesCount = count($rules);
        $rules[$translationName] = ValidationHelper::getArrayRules(size: $rulesCount);

        return $rules;
    }

    public static function getArrayForSelect(): array
    {
        return array_map(function ($locale) {
            return ['id' => $locale, 'text' => $locale];
        }, array_keys(config('laravel-translations.locales')));
    }

    public static function validateLocale(string $locale): void
    {
        if (! in_array($locale, array_keys(config('laravel-translations.locales')))) {
            throw new LocaleNotDefinedException($locale);
        }
    }
}

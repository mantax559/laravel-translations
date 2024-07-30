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

        foreach (self::getValidatedLocales() as $locale) {
            $localeRules = [];
            $rulesCount = 1;

            if ($withTranslationStatus) {
                $localeRules["$translationName.$locale.".config('laravel-translations.translation_status_column')] = ValidationHelper::getEnumRules(enum: TranslationStatusEnum::class);
                $rulesCount++;
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

            $defaultColumnRule = empty($textColumns) && empty($stringColumns)
                ? ValidationHelper::getStringRules(required: false)
                : ValidationHelper::getStringRules(required: 'required_with:'.implode(',', $requiredFields));

            $localeRules["$translationName.$locale.".config('laravel-translations.default_column')] = $defaultColumnRule;

            $localeRules["$translationName.$locale"] = ValidationHelper::getArrayRules(min: $rulesCount, max: $rulesCount); // TODO: Add "size" to getArrayRules

            $rules = array_merge($rules, $localeRules);
        }

        if (! empty($rules)) {
            $rulesCount = count($rules);
            $rules[$translationName] = ValidationHelper::getArrayRules(min: $rulesCount, max: $rulesCount); // TODO: Add "size" to getArrayRules
        }

        return $rules;
    }

    public static function getArrayForSelect(): array
    {
        return array_map(function ($locale) {
            return ['id' => $locale, 'text' => $locale];
        }, self::getValidatedLocales());
    }

    public static function getValidatedLocales(?string $locale = null): array
    {
        $locales = self::getLocales();

        $locale = $locale ?? app()->getLocale();

        self::validateLocale($locale);

        return $locales;
    }

    public static function validateLocale(string $locale): void
    {
        if (! in_array($locale, self::getLocales())) {
            throw new LocaleNotDefinedException($locale);
        }
    }

    private static function getLocales(): array
    {
        return array_unique(array_merge(
            config('laravel-translations.locales'),
            [
                config('laravel-translations.primary_locale'),
                config('laravel-translations.fallback_locale'),
            ]
        ));
    }
}

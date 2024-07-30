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
            $rules = [];
            $rulesCount = 1;

            if ($withTranslationStatus) {
                $rules["$translationName.$locale.".config('laravel-translations.translation_status_column')] = ValidationHelper::getEnumRules(enum: TranslationStatusEnum::class);
                $rulesCount++;
            }

            if (! empty($textColumns) || ! empty($stringColumns)) {
                $required = [];

                foreach ($textColumns as $textColumn) {
                    $required[] = $locale.'.'.$textColumn;
                    $rules["$translationName.$locale.$textColumn"] = ValidationHelper::getTextRules(required: false);
                }

                foreach ($stringColumns as $stringColumn) {
                    $required[] = $locale.'.'.$stringColumn;
                    $rules["$translationName.$locale.$stringColumn"] = ValidationHelper::getStringRules(required: false);
                }

                $rules["$translationName.$locale.".config('laravel-translations.default_column')] = ValidationHelper::getStringRules(required: 'required_with:'.implode(',', $required));
                $rulesCount++;
            } else {
                $rules["$translationName.$locale.".config('laravel-translations.default_column')] = ValidationHelper::getStringRules(required: false);
            }

            $rules["$translationName.$locale"] = ValidationHelper::getArrayRules(min: $rulesCount, max: $rulesCount); // TODO: Add "size" to getArrayRules
        }

        if (! empty($rules)) {
            $rulesCount = count($rules);
            $rules[$translationName] = ValidationHelper::getArrayRules(min: $rulesCount, max: $rulesCount); // TODO: Add "size" to getArrayRules
        }

        return $rules;
    }

    public static function getArrayForSelect(): array
    {
        $array = [];

        foreach (self::getValidatedLocales() as $locale) {
            $array[] = [
                'id' => $locale,
                'text' => $locale,
            ];
        }

        return $array;
    }

    public static function getValidatedLocales(?string $locale = null): array
    {
        $locales = array_unique(
            array_merge(
                config('laravel-translations.locales'),
                [
                    config('laravel-translations.primary_locale'),
                    config('laravel-translations.fallback_locale'),
                ]
            )
        );

        if (empty($locale)) {
            $locale = app()->getLocale();
        }

        if (! in_array($locale, $locales)) {
            throw new LocaleNotDefinedException($locale);
        }

        return $locales;
    }
}

<?php

namespace Mantax559\LaravelTranslations\Helpers;

use Illuminate\Support\Facades\File;
use Mantax559\LaravelHelpers\Helpers\ValidationHelper;
use Mantax559\LaravelTranslations\Enums\TranslationStatusEnum;
use Mantax559\LaravelTranslations\Exceptions\LocaleNotDefinedException;

class TranslationHelper
{
    public static function getValidationRules(string $translationName, array $textColumns = [], array $stringColumns = [], bool $withTranslationStatus = false): array
    {
        $rules = [];

        foreach (self::getLocales() as $locale) {
            $localeRules = [];

            if ($withTranslationStatus) {
                $localeRules["$translationName.$locale.translation_status"] = ValidationHelper::getEnumRules(enum: TranslationStatusEnum::class);
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
        }, self::getLocales());
    }

    public static function validateLocale(string $locale): void
    {
        if (! in_array($locale, self::getLocales())) {
            throw new LocaleNotDefinedException($locale);
        }
    }

    public static function getLocales(): array
    {
        $langPath = base_path('lang');

        if (! File::exists($langPath)) {
            return [];
        }

        if (! file_exists($langPath) || ! is_dir($langPath)) {
            return [];
        }

        $jsonFiles = [];

        $files = scandir($langPath);

        foreach ($files as $file) {
            $filePath = $langPath.DIRECTORY_SEPARATOR.$file;

            if (is_file($filePath) && cmprstr(pathinfo($filePath, PATHINFO_EXTENSION), 'json')) {
                $jsonFiles[] = pathinfo($filePath, PATHINFO_FILENAME);
            }
        }

        return $jsonFiles;
    }
}

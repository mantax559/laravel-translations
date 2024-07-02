<?php

namespace Mantax559\LaravelTranslations\Helpers;

use Mantax559\LaravelHelpers\Helpers\ValidationHelper;
use Mantax559\LaravelTranslations\Enums\TranslationStatusEnum;

class TranslationHelper
{
    public static function getValidationRules(string $translationName, bool $withDescription = false, bool $withTranslationStatus = false): array
    {
        $rules = [];

        foreach (config('laravel-translations.locales') as $locale) {
            $rules = [];
            $rulesCount = 1;

            if ($withTranslationStatus) {
                $rules["$translationName.$locale.translation_status"] = ValidationHelper::getEnumRules(enum: TranslationStatusEnum::class);
                $rulesCount++;
            }

            if ($withDescription) {
                $rules["$translationName.$locale.title"] = ValidationHelper::getStringRules(required: "required_with:$locale.description");
                $rules["$translationName.$locale.description"] = ValidationHelper::getTextRules(required: false);
                $rulesCount++;
            } else {
                $rules["$translationName.$locale.title"] = ValidationHelper::getStringRules(required: false);
            }

            $rules["$translationName.$locale"] = ValidationHelper::getArrayRules(min: $rulesCount, max: $rulesCount);
        }

        if (! empty($rules)) {
            $rulesCount = count($rules);
            $rules[$translationName] = ValidationHelper::getArrayRules(min: $rulesCount, max: $rulesCount); // TODO: Add "size" to getArrayRules
        }

        return $rules;
    }
}

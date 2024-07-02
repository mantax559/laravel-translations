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

    public static function getMostAccurate($model, string $idKey, bool $withDescription = false, array $parentModelNames = []): array
    {
        $modelTranslation = self::getTranslation($model);

        $fullTitle = self::buildFullTitle($modelTranslation->title, $model, $parentModelNames);

        $data = [
            'id' => $modelTranslation->$idKey,
            'translation_status' => $modelTranslation->translation_status,
            'locale' => $modelTranslation->locale,
            'title' => $fullTitle,
            'title_value' => $modelTranslation->title,
        ];

        if ($withDescription) {
            $data['description_value'] = $modelTranslation->description;
        }

        return $data;
    }

    private static function getTranslation($model)
    {
        if (! empty($model->translation)) {
            return $model->translation;
        }

        $translationStatuses = [
            TranslationStatusEnum::Confirmed,
            TranslationStatusEnum::Manual,
            TranslationStatusEnum::External,
            TranslationStatusEnum::Auto,
        ];

        $modelTranslations = $model->translations;
        foreach ($translationStatuses as $status) {
            $translation = $modelTranslations->where('translation_status', $status)->first();

            if ($translation) {
                return $translation;
            }
        }

        return null;
    }

    private static function buildFullTitle(string $title, $model, array $parentModelNames): string
    {
        $fullTitle = $title;
        $parentModel = $model;

        foreach ($parentModelNames as $parentModelName) {
            $parentModel = $parentModel->$parentModelName;
            $parentModelTitle = empty($parentModel->translation)
                ? '<i><span class="text-secondary">'.__('Nėra vertimo').'</span></i>'
                : $parentModel->translation->title;

            $fullTitle = $parentModelTitle.' <i class="fas fa-angle-right text-secondary px-2"></i> '.$fullTitle;
        }

        return $fullTitle;
    }
}

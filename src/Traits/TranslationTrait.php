<?php

namespace Mantax559\LaravelTranslations\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Schema;
use Mantax559\LaravelTranslations\Enums\TranslationStatusEnum;
use Mantax559\LaravelTranslations\Helpers\TranslationHelper;

trait TranslationTrait
{
    private Model $modelTranslation;

    public function __construct()
    {
        $this->initializeModelTranslation();
        $this->with[] = 'translation';
    }

    public static function bootTranslationTrait(): void
    {
        static::saving(function (Model $model) {
            $defaultTranslationColumn = $this->defaultTranslationColumn;
            $model->$defaultTranslationColumn = format_string($model->$defaultTranslationColumn);

            if (empty($model->$defaultTranslationColumn)) {
                return false;
            }

            return true;
        });
    }

    public function getAttribute($key): mixed
    {
        if ($this->isTranslationAttribute($key)) {
            return $this->translation?->$key ?? null;
        }

        return parent::getAttribute($key);
    }

    public function saveTranslations(array $translations): void
    {
        foreach ($translations as $locale => $translation) {
            $locale = $this->formatAndValidateLocale($locale);
            $translationModel = $this->translate($locale) ?? new $this->translationModel();
            $translationModel->fill($translation);
            $translationModel->locale = $locale;
            $this->translations()->save($translationModel);
        }
    }

    public function translation(): HasOne
    {
        return $this->hasOne($this->modelTranslation)
            ->where('locale', app()->getLocale());
    }

    public function translations(): HasMany
    {
        return $this->hasMany($this->modelTranslation)
            ->whereIn('locale', config('laravel-translations.locales'))
            ->orderByRaw($this->hasTranslationStatus()
                ? $this->getTranslationStatusOrderByClause()
                : $this->getLocaleOrderByClause());
    }

    public function translate(string $locale): mixed
    {
        return $this->translations->where('locale', $this->formatAndValidateLocale($locale))->first();
    }

    public function hasTranslation(string $locale): bool
    {
        return $this->translations->where('locale', $this->formatAndValidateLocale($locale))->isNotEmpty();
    }

    public function scopeOrderByTranslation(Builder $query, string $translationField, bool $asc = true): Builder
    {
        $tableColumns = Schema::getColumnListing($this->getTable());

        $selectClause = array_map(fn ($column) => "{$this->getTable()}.$column", $tableColumns);

        $translationTableAlias = "{$this->modelTranslation->getTable()} as translation";

        $foreignKey = "translation.{$this->getForeignKey()}";
        $primaryKey = "{$this->getTable()}.{$this->getKeyName()}";

        $orderDirection = $asc ? 'asc' : 'desc';

        return $query
            ->select($selectClause)
            ->leftJoin($translationTableAlias, $foreignKey, '=', $primaryKey)
            ->orderBy("translation.$translationField", $orderDirection)
            ->groupBy($selectClause);
    }

    private function initializeModelTranslation(): void
    {
        $this->modelTranslation = new $this->translationModel();
    }

    private function isTranslationAttribute(string $key): bool
    {
        return in_array($key, $this->modelTranslation->getFillable(), true);
    }

    private function hasTranslationStatus(): bool
    {
        return $this->modelTranslation->hasCast(config('laravel-translations.translation_status_column'));
    }

    private function formatAndValidateLocale(string $locale): string
    {
        $formattedLocale = format_string($locale, [3, 7]);
        TranslationHelper::validateLocale($formattedLocale);

        return $formattedLocale;
    }

    private function getLocaleOrderByClause(): string
    {
        $locales = config('laravel-translations.locales');
        $formattedLocales = implode("', '", $locales);

        return "FIELD(locale, '$formattedLocales') ASC";
    }

    private function getTranslationStatusOrderByClause(): string
    {
        $translationStatusColumn = config('laravel-translations.translation_status_column');

        $translationStatusValues = TranslationStatusEnum::getArray();
        $formattedTranslationStatusValues = implode("', '", $translationStatusValues);

        return "FIELD($translationStatusColumn, '$formattedTranslationStatusValues') ASC";
    }
}

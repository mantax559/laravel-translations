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

    public static function bootTranslatable(): void
    {
        static::saving(function (Model $model) {
            $defaultColumn = config('laravel-translations.default_column');
            $model->$defaultColumn = format_string($model->$defaultColumn);

            if (empty($model->$defaultColumn)) {
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
            $locale = $this->formatLocale($locale);
            $translationModel = $this->translate($locale) ?? new $this->translationModel();
            $translationModel->fill($translation);
            $translationModel->locale = $locale;
            $this->translations()->save($translationModel);
        }
    }

    public function translation(): HasOne
    {
        return $this->hasOne($this->modelTranslation)
            ->whereIn('locale', TranslationHelper::getValidatedLocales())
            ->orderByRaw("FIELD(locale, '".implode("', '", TranslationHelper::getValidatedLocales())."') ASC");
    }

    public function translations(): HasMany
    {
        return $this->hasMany($this->modelTranslation)
            ->when($this->hasTranslationStatus(), fn ($query) => $query->orderByRaw('FIELD('.config('laravel-translations.translation_status_column').", '".implode("', '", TranslationStatusEnum::getArray())."') ASC"));
    }

    public function translate(string $locale): mixed
    {
        $locale = $this->formatLocale($locale);

        return $this->translations->where('locale', $locale)->first();
    }

    public function hasTranslation(string $locale): bool
    {
        $locale = $this->formatLocale($locale);

        return $this->translations->where('locale', $locale)->isNotEmpty();
    }

    public function scopeOrderByTranslation(Builder $query, string $translationField, bool $asc = true): Builder
    {
        $categoryColumns = Schema::getColumnListing($this->getTable());
        $selectClause = array_map(function ($column) {
            return "{$this->getTable()}.$column";
        }, $categoryColumns);

        return $query
            ->select($selectClause)
            ->leftJoin("{$this->modelTranslation->getTable()} as translation", "translation.{$this->getForeignKey()}", '=', "{$this->getTable()}.{$this->getKeyName()}")
            ->orderBy("translation.$translationField", $asc ? 'asc' : 'desc')
            ->groupBy($selectClause);
    }

    private function initializeModelTranslation(): void
    {
        $this->modelTranslation = new $this->translationModel();
    }

    private function isTranslationAttribute(string $key): bool
    {
        return array_key_exists($key, array_flip($this->modelTranslation->getFillable()));
    }

    private function hasTranslationStatus(): bool
    {
        return array_key_exists(config('laravel-translations.translation_status_column'), $this->modelTranslation->getCasts());
    }

    private function formatLocale(string $locale): string
    {
        $locale = format_string($locale, [3, 7]);

        TranslationHelper::getValidatedLocales($locale);

        return $locale;
    }
}

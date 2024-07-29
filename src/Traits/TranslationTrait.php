<?php

namespace Mantax559\LaravelTranslations\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Schema;
use Mantax559\LaravelTranslations\Enums\TranslationStatusEnum;
use Mantax559\LaravelTranslations\Exceptions\LocaleNotDefinedException;

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
            $model->title = format_string($model->title);

            if (empty($model->title)) {
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
            ->whereIn('locale', $this->validateAndGetLocales())
            ->orderByRaw("FIELD(locale, '".implode("', '", $this->validateAndGetLocales())."') ASC");
    }

    public function translations(): HasMany
    {
        return $this->hasMany($this->modelTranslation)
            ->when($this->hasTranslationStatus(), fn ($query) => $query->orderByRaw("FIELD(translation_status, '".implode("', '", TranslationStatusEnum::getArray())."') ASC"));
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
        return array_key_exists('translation_status', $this->modelTranslation->getCasts());
    }

    private function validateAndGetLocales(?string $locale = null): array
    {
        $locales = array_unique(array_merge(config('laravel-translations.locales'), [
            config('laravel-translations.primary_locale'),
            config('laravel-translations.fallback_locale'),
        ]));

        $currentLocale = $locale ?? app()->getLocale();
        if (! in_array($currentLocale, $locales)) {
            throw new LocaleNotDefinedException($currentLocale);
        }

        return $locales;
    }

    private function formatLocale(string $locale): string
    {
        $locale = format_string($locale, [3, 7]);

        $this->validateAndGetLocales($locale);

        return $locale;
    }
}

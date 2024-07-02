<?php

namespace Mantax559\LaravelTranslations\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Mantax559\LaravelTranslations\Enums\TranslationStatusEnum;

trait TranslationTrait
{
    private Model $modelTranslation;

    public function __construct()
    {
        $this->initializeModelTranslation();
        $this->with[] = 'translation';
    }

    public function getAttribute($key): mixed
    {
        if ($this->isTranslationAttribute($key)) {
            return $this->translation?->$key ?? null;
        }

        return parent::getAttribute($key);
    }

    public function translation(): HasOne
    {
        return $this->hasOne($this->modelTranslation)
            ->whereIn('locale', $this->getLocales())
            ->orderByRaw("FIELD(locale, {$this->getLocaleValues()}) ASC");
    }

    public function translations(): HasMany
    {
        return $this->hasMany($this->modelTranslation)
            ->when($this->hasTranslationStatus(), fn ($query) => $query->orderByRaw("FIELD(translation_status, {$this->getTranslationStatusValues()}) ASC")
            );
    }

    public function translate(string $locale): mixed
    {
        return $this->translations->where('locale', $locale)->first();
    }

    public function hasTranslation(string $locale): bool
    {
        return $this->translations->where('locale', $locale)->isNotEmpty();
    }

    public function scopeOrderByTranslation(Builder $query, string $translationField, bool $desc = false): Builder
    {
        return $query
            ->leftJoin($this->modelTranslation->getTable(), "{$this->modelTranslation->getTable()}.{$this->getForeignKey()}", '=', "{$this->getTable()}.{$this->getKeyName()}")
            ->orderBy("{$this->modelTranslation->getTable()}.{$translationField}", $desc ? 'desc' : 'asc');
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

    private function getLocales(): array
    {
        return array_unique([
            app()->getLocale(),
            config('app.locale'),
            config('app.fallback_locale'),
        ]);
    }

    private function getLocaleValues(): string
    {
        return "'".implode("', '", $this->getLocales())."'";
    }

    private function getTranslationStatusValues(): string
    {
        return "'".implode("', '", TranslationStatusEnum::getArray())."'";
    }
}

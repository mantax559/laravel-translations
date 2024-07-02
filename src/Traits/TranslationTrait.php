<?php

namespace Mantax559\LaravelTranslations\Traits;

use App\Enums\TranslationStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

trait TranslationTrait
{
    private Model $modelTranslation;

    public function __construct()
    {
        $this->modelTranslation = (new $this->translationModel());
        $this->with[] = 'translation';
    }

    public function getAttribute($key): mixed
    {
        if ($this->isTranslationAttribute($key)) {
            if (! empty($this->translation)) {
                return $this->translation->$key;
            }

            return null;
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
            ->when($this->hasTranslationStatus(), function ($query) {
                $query->orderByRaw("FIELD(translation_status, {$this->getTranslationStatusValues()}) ASC");
            });
    }

    public function translate(string $locale): mixed
    {
        return $this->translations->where('locale', $locale)->first();
    }

    public function hasTranslation(string $locale): bool
    {
        return count($this->translations->where('locale', $locale));
    }

    public function scopeOrderByTranslation(Builder $query, string $translationField, bool $desc = false): Builder
    {
        return $query
            ->leftJoin($this->modelTranslation->getTable(), "{$this->modelTranslation->getTable()}.{$this->getForeignKey()}", '=', "{$this->getTable()}.{$this->getKeyName()}")
            ->orderBy("{$this->modelTranslation->getTable()}.{$translationField}", ($desc ? 'desc' : 'asc'));
    }

    private function isTranslationAttribute(string $key): bool
    {
        return isset(array_flip($this->modelTranslation->getFillable())[$key]);
    }

    private function hasTranslationStatus(): bool
    {
        return isset($this->modelTranslation->getCasts()['translation_status']);
    }

    private function getLocales(): array
    {
        return array_unique([app()->getLocale(), config('app.locale'), config('app.fallback_locale')]);
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

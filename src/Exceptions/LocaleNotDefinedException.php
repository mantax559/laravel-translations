<?php

namespace Mantax559\LaravelTranslations\Exceptions;

use Exception;

class LocaleNotDefinedException extends Exception
{
    public function __construct(string $locale)
    {
        parent::__construct("Locale '$locale' is not defined in the settings.");
    }

    public static function make(string $locale): self
    {
        return new self($locale);
    }
}

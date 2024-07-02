<?php

namespace Mantax559\LaravelTranslations\Enums;

use Mantax559\LaravelHelpers\Traits\EnumTrait;

enum TranslationStatusEnum: string
{
    use EnumTrait;

    case Confirmed = 'confirmed';
    case Manual = 'manual';
    case External = 'external';
    case Auto = 'auto';
}

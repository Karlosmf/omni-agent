<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum LeadTemperature: string implements HasColor, HasLabel
{
    case Cool = 'cool';
    case Warm = 'warm';
    case Hot = 'hot';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Cool => 'Frío',
            self::Warm => 'Tibio',
            self::Hot => 'Caliente',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Cool => 'info',
            self::Warm => 'warning',
            self::Hot => 'danger',
        };
    }
}

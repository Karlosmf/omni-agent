<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Currency: string implements HasLabel
{
    case ARS = 'ARS';
    case USD = 'USD';
    case BRL = 'BRL';
    case OTHER = 'OTHER';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::ARS => 'Pesos Argentinos (ARS)',
            self::USD => 'Dólares (USD)',
            self::BRL => 'Reales (BRL)',
            self::OTHER => 'Otro',
        };
    }
}

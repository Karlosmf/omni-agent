<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum BookingStatus: string implements HasColor, HasLabel
{
    case Presupuesto = 'presupuesto';
    case Senado = 'senado';
    case Emitido = 'emitido';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Presupuesto => 'Presupuesto',
            self::Senado => 'Señado',
            self::Emitido => 'Emitido',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Presupuesto => 'gray',
            self::Senado => 'warning',
            self::Emitido => 'success',
        };
    }
}

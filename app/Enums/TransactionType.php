<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TransactionType: string implements HasColor, HasLabel
{
    case Cobro = 'cobro';
    case Pago = 'pago';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Cobro => 'Cobro (Ingreso)',
            self::Pago => 'Pago (Egreso)',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Cobro => 'success',
            self::Pago => 'danger',
        };
    }
}

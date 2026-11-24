<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum BookingStatus: string implements HasColor, HasLabel
{
    case Borrador = 'borrador';
    case Presupuesto = 'presupuesto';
    case Senado = 'senado';
    case Emitido = 'emitido';
    case Cancelado = 'cancelado';

    /**
     * Statuses that count as a sale.
     *
     * @return list<self>
     */
    public static function confirmed(): array
    {
        return [self::Senado, self::Emitido];
    }

    /**
     * Whether this status counts as a sale (señado or emitido).
     */
    public function isConfirmed(): bool
    {
        return in_array($this, self::confirmed(), true);
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Borrador => 'Borrador',
            self::Presupuesto => 'Presupuesto',
            self::Senado => 'Señado',
            self::Emitido => 'Emitido',
            self::Cancelado => 'Cancelado',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Borrador => 'gray',
            self::Presupuesto => 'info',
            self::Senado => 'warning',
            self::Emitido => 'success',
            self::Cancelado => 'danger',
        };
    }
}

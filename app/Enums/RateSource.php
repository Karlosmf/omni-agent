<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Where an ARS/USD exchange rate came from.
 */
enum RateSource: string implements HasLabel
{
    case Oficial = 'oficial';
    case Mep = 'mep';
    case Tarjeta = 'tarjeta';
    case Custom = 'custom';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Oficial => 'Dólar Oficial',
            self::Mep => 'Dólar MEP',
            self::Tarjeta => 'Dólar Tarjeta',
            self::Custom => 'Personalizado',
        };
    }

    /**
     * The DolarAPI "casa" identifier for this source, or null for a manual rate.
     */
    public function dolarApiHouse(): ?string
    {
        return match ($this) {
            self::Oficial => 'oficial',
            self::Mep => 'bolsa',
            self::Tarjeta => 'tarjeta',
            self::Custom => null,
        };
    }

    /**
     * @return list<self>
     */
    public static function quoted(): array
    {
        return [self::Oficial, self::Mep, self::Tarjeta];
    }
}

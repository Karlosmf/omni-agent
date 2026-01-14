<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ServiceType: string implements HasLabel
{
    case Flight = 'flight';
    case Hotel = 'hotel';
    case Transfer = 'transfer';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Flight => 'Vuelo',
            self::Hotel => 'Hotel',
            self::Transfer => 'Traslado',
        };
    }
}

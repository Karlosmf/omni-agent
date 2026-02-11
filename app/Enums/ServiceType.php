<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ServiceType: string implements HasLabel
{
    case Flight = 'flight';
    case Hotel = 'hotel';
    case HotelTransfer = 'hotel_transfer';
    case Transfer = 'transfer';
    case Land = 'land';
    case Assistance = 'assistance';
    case Bus = 'bus';
    case Cruise = 'cruise';
    case Other = 'other';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Flight => 'Vuelo',
            self::Hotel => 'Hotel',
            self::HotelTransfer => 'Hotel y Traslado',
            self::Transfer => 'Traslado',
            self::Land => 'Terrestre',
            self::Assistance => 'Asistencia al viajero',
            self::Bus => 'Bus',
            self::Cruise => 'Crucero',
            self::Other => 'Otro',
        };
    }
}

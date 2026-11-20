<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum LeadStatus: string implements HasColor, HasLabel
{
    case New = 'new';
    case PendingReview = 'pending_review';
    case Contacted = 'contacted';
    case Quoted = 'quoted';
    case Booked = 'booked';
    case Closed = 'closed';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::New => 'Nueva',
            self::PendingReview => 'Por verificar',
            self::Contacted => 'En curso',
            self::Quoted => 'Cotizada',
            self::Booked => 'Reservada',
            self::Closed => 'Cerrada',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::New => 'gray',
            self::PendingReview => 'warning',
            self::Contacted => 'info',
            self::Quoted => 'primary',
            self::Booked, self::Closed => 'success',
        };
    }
}

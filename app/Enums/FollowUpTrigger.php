<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum FollowUpTrigger: string implements HasLabel
{
    case LeadUnassigned = 'lead_unassigned';
    case LeadNotContacted = 'lead_not_contacted';
    case LeadHot = 'lead_hot';
    case BookingNotOpened = 'booking_not_opened';
    case BookingViewedOften = 'booking_viewed_often';
    case BookingExpired = 'booking_expired';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::LeadUnassigned => 'Lead nuevo sin agente asignado',
            self::LeadNotContacted => 'Lead sin contactar',
            self::LeadHot => 'Lead que pide hablar con una persona',
            self::BookingNotOpened => 'Presupuesto enviado y no abierto',
            self::BookingViewedOften => 'Presupuesto abierto 3 veces o más sin señar',
            self::BookingExpired => 'Presupuesto vencido',
        };
    }

    public function isForLeads(): bool
    {
        return in_array($this, [self::LeadUnassigned, self::LeadNotContacted, self::LeadHot], true);
    }
}

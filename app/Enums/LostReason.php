<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Why an inquiry was closed without a sale.
 */
enum LostReason: string implements HasLabel
{
    case Price = 'price';
    case Dates = 'dates';
    case OtherAgency = 'other_agency';
    case NoReply = 'no_reply';
    case ChangedPlans = 'changed_plans';
    case Other = 'other';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Price => 'Precio',
            self::Dates => 'Fechas o disponibilidad',
            self::OtherAgency => 'Compró en otra agencia',
            self::NoReply => 'Dejó de responder',
            self::ChangedPlans => 'Cambió de planes',
            self::Other => 'Otro',
        };
    }
}

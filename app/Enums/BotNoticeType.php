<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * What the receptionist bot tells the team through the notices tray.
 */
enum BotNoticeType: string implements HasColor, HasLabel
{
    case Handoff = 'handoff';
    case VerifyNewInquiry = 'verify_new_inquiry';
    case DataComplete = 'data_complete';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Handoff => 'Derivación a una persona',
            self::VerifyNewInquiry => 'Verificar consulta nueva',
            self::DataComplete => 'Datos completos: lista para cotizar',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Handoff => 'danger',
            self::VerifyNewInquiry => 'warning',
            self::DataComplete => 'success',
        };
    }
}

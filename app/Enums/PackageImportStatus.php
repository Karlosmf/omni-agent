<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PackageImportStatus: string implements HasColor, HasLabel
{
    case Queued = 'queued';
    case Extracting = 'extracting';
    case ReadyForReview = 'ready_for_review';
    case Published = 'published';
    case Failed = 'failed';
    case Discarded = 'discarded';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Queued => 'En cola',
            self::Extracting => 'Procesando',
            self::ReadyForReview => 'Listo para revisar',
            self::Published => 'Publicado',
            self::Failed => 'Falló',
            self::Discarded => 'Descartado',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Queued, self::Extracting => 'warning',
            self::ReadyForReview => 'info',
            self::Published => 'success',
            self::Failed => 'danger',
            self::Discarded => 'gray',
        };
    }

    public function isProcessing(): bool
    {
        return in_array($this, [self::Queued, self::Extracting], true);
    }
}

<?php

namespace App\Enums;

enum SingleSupplementType: string
{
    case Fixed = 'fixed';
    case Percent = 'percent';

    public function label(): string
    {
        return match ($this) {
            self::Fixed => 'Monto fijo',
            self::Percent => 'Porcentaje sobre precio base',
        };
    }

    /**
     * Options array for Filament Select components.
     *
     * @return array<string, string>
     */
    public static function toOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}

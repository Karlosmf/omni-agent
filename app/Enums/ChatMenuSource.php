<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Where the options of a chat menu come from.
 */
enum ChatMenuSource: string implements HasLabel
{
    /** The destinations of the active packages, with how many packages each has. */
    case Destinations = 'destinations';

    /** The active packages, only those of the destination chosen in the previous menu when there is one. */
    case Packages = 'packages';

    /** Options typed by the team. */
    case Options = 'options';

    public function getLabel(): string
    {
        return match ($this) {
            self::Destinations => 'Destinos de los paquetes activos',
            self::Packages => 'Paquetes activos',
            self::Options => 'Opciones escritas a mano',
        };
    }
}

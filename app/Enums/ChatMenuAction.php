<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * What happens when the customer taps an option of a chat menu.
 */
enum ChatMenuAction: string implements HasLabel
{
    /** The option is read as the customer's words and the bot (or the assistant) answers as usual. */
    case Bot = 'bot';

    /** A fixed text goes back. */
    case Reply = 'reply';

    /** Another menu opens; a chosen destination narrows a menu of packages. */
    case Menu = 'menu';

    public function getLabel(): string
    {
        return match ($this) {
            self::Bot => 'Sigue la conversación con el bot',
            self::Reply => 'Responder un texto fijo',
            self::Menu => 'Abrir otro menú',
        };
    }
}

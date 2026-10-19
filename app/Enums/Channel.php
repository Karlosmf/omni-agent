<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Where a conversation with a customer happens.
 */
enum Channel: string implements HasColor, HasLabel
{
    case Web = 'web';
    case WhatsApp = 'whatsapp';
    case Instagram = 'instagram';
    case Messenger = 'messenger';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Web => 'Web',
            self::WhatsApp => 'WhatsApp',
            self::Instagram => 'Instagram',
            self::Messenger => 'Messenger',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Web => 'gray',
            self::WhatsApp => 'success',
            self::Instagram => 'danger',
            self::Messenger => 'info',
        };
    }

    /**
     * Hours after the customer's last message during which free-form replies are allowed.
     */
    public function windowHours(): int
    {
        return 24;
    }

    /**
     * Channels that live inside the shared inbox (everything but the website widget).
     *
     * @return list<self>
     */
    public static function inbox(): array
    {
        return [self::WhatsApp, self::Instagram, self::Messenger];
    }

    /**
     * Instagram and Messenger share Meta's messaging API; customers there have no phone number.
     */
    public function isSocial(): bool
    {
        return $this === self::Instagram || $this === self::Messenger;
    }
}

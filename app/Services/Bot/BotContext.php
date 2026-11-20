<?php

namespace App\Services\Bot;

/**
 * What the bot knows when answering one message. It holds plain data so the internal test chat can run the same agent without a real conversation.
 */
class BotContext
{
    /**
     * @param  array<string, mixed>  $trip  The active inquiry's data.
     * @param  array<string, mixed>  $profile  Data that persists across the contact's trips.
     * @param  list<array{id: int, destino: ?string, fechas: ?string, activa: bool}>  $openInquiries
     * @param  list<array{role: string, content: string}>  $history  Oldest first.
     */
    public function __construct(
        public array $trip,
        public array $profile = [],
        public array $openInquiries = [],
        public array $history = [],
        public ?string $contactName = null,
    ) {}
}

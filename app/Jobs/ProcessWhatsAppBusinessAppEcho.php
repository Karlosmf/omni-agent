<?php

namespace App\Jobs;

use App\Services\WhatsApp\WhatsAppConversationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessWhatsAppBusinessAppEcho implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [10, 60];
    }

    /**
     * @param  array<string, mixed>  $echo  A "message_echoes[]" item of the "smb_message_echoes" webhook field.
     */
    public function __construct(public array $echo, public ?string $phoneNumberId = null) {}

    public function handle(WhatsAppConversationService $conversations): void
    {
        $conversations->receiveBusinessAppEcho($this->echo, $this->phoneNumberId);
    }
}

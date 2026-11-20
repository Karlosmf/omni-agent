<?php

namespace App\Jobs;

use App\Services\WhatsApp\WhatsAppConversationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessIncomingWhatsApp implements ShouldQueue
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
     * @param  array<string, mixed>  $message
     * @param  array<string, mixed>|null  $rawPayload  The provider's event as it arrived, kept with the stored message.
     */
    public function __construct(public array $message, public ?string $profileName = null, public ?string $phoneNumberId = null, public ?array $rawPayload = null) {}

    public function handle(WhatsAppConversationService $conversations): void
    {
        $conversations->receive($this->message, $this->profileName, $this->phoneNumberId, $this->rawPayload);
    }
}

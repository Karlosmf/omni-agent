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
     */
    public function __construct(public array $message, public ?string $profileName = null, public ?string $phoneNumberId = null) {}

    public function handle(WhatsAppConversationService $conversations): void
    {
        $conversations->receive($this->message, $this->profileName, $this->phoneNumberId);
    }
}

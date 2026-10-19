<?php

namespace App\Jobs;

use App\Services\Channels\InboundMessage;
use App\Services\WhatsApp\WhatsAppConversationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessIncomingSocialMessage implements ShouldQueue
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

    public function __construct(public InboundMessage $message) {}

    public function handle(WhatsAppConversationService $conversations): void
    {
        $conversations->receiveSocial($this->message);
    }
}

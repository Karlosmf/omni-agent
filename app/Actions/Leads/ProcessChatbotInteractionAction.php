<?php

namespace App\Actions\Leads;

use App\Models\Lead;
use App\Services\AiConciergeService;
use Illuminate\Support\Facades\Log;

class ProcessChatbotInteractionAction
{
    public function __construct(protected AiConciergeService $aiService) {}

    /**
     * Answer the customer's message. The AI replies and updates the lead (data, temperature, qualification) in one call.
     *
     * @param  array  $messagesContext  Kept for older callers; the agent reads the lead's own message history.
     * @return string The AI response content
     */
    public function execute(string $userMsg, Lead $lead, array $messagesContext = [], bool $recordUserMessage = true): string
    {
        try {
            return $this->aiService->processMessage($userMsg, $lead, $recordUserMessage);
        } catch (\Throwable $e) {
            Log::error('Chatbot Interaction Error: '.$e->getMessage());

            return 'Disculpá, tuve una pequeña desconexión. ¿Me lo repetís?';
        }
    }
}

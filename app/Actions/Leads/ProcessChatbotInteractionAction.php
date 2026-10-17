<?php

namespace App\Actions\Leads;

use App\Models\Lead;
use App\Services\AiConciergeService;
use App\Services\LeadEnrichmentService;
use Illuminate\Support\Facades\Log;

class ProcessChatbotInteractionAction
{
    public function __construct(
        protected AiConciergeService $aiService,
        protected LeadEnrichmentService $enrichment = new LeadEnrichmentService,
    ) {}

    /**
     * Handle the chatbot message processing, including AI interaction and Lead update.
     *
     * @param  array  $messagesContext  The previous messages history to provide context
     * @return string The AI response content
     */
    public function execute(string $userMsg, Lead $lead, array $messagesContext = [], bool $recordUserMessage = true): string
    {
        try {
            // 1. Process with AI to get a reply
            $replyContent = $recordUserMessage
                ? $this->aiService->processMessage($userMsg, $lead)
                : $this->aiService->processMessage($userMsg, $lead, false);

            // 2. Extract and update lead data
            $queryContext = array_slice($messagesContext, -10);

            if (strlen($userMsg) > 2 || count($queryContext) > 0) {
                $extractionContext = $userMsg;
                if (! empty($queryContext)) {
                    $extractionContext = json_encode($queryContext)."\nLAST_MSG: ".$userMsg;
                }

                $extraction = $this->aiService->extractLeadData($extractionContext);

                $this->enrichment->apply($lead, $extraction);
            }

            return $replyContent;

        } catch (\Throwable $e) {
            Log::error('Chatbot Interaction Error: '.$e->getMessage());

            return 'Disculpá, tuve una pequeña desconexión. ¿Me lo repetís?';
        }
    }
}

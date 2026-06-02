<?php

namespace App\Actions\Leads;

use App\Models\Lead;
use App\Services\AiConciergeService;
use Illuminate\Support\Facades\Log;

class ProcessChatbotInteractionAction
{
    protected AiConciergeService $aiService;

    public function __construct(AiConciergeService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Handle the chatbot message processing, including AI interaction and Lead update.
     *
     * @param string $userMsg
     * @param Lead $lead
     * @param array $messagesContext The previous messages history to provide context
     * @return string The AI response content
     */
    public function execute(string $userMsg, Lead $lead, array $messagesContext = []): string
    {
        try {
            // 1. Process with AI to get a reply
            $replyContent = $this->aiService->processMessage($userMsg, $lead);

            // 2. Extract and update lead data
            $queryContext = array_slice($messagesContext, -10);

            if (strlen($userMsg) > 2 || count($queryContext) > 0) {
                $extractionContext = $userMsg;
                if (!empty($queryContext)) {
                    $extractionContext = json_encode($queryContext) . "\nLAST_MSG: " . $userMsg;
                }

                $extraction = $this->aiService->extractLeadData($extractionContext);

                if (!empty($extraction)) {
                    $currentAiData = $lead->ai_data ?? [];

                    $newAiData = array_merge($currentAiData, array_filter([
                        'destino' => $extraction['destino'] ?? null,
                        'presupuesto' => $extraction['presupuesto'] ?? null,
                        'pasajeros' => $extraction['pasajeros'] ?? null,
                    ]));

                    $updateData = [
                        'ai_data' => $newAiData,
                        'ai_summary' => $extraction['resumen'] ?? $lead->ai_summary,
                        'needs_human_attention' => ($extraction['requiere_atencion'] ?? false) || $lead->needs_human_attention,
                    ];

                    // Update customer name if extracted and still generic
                    if (!empty($extraction['nombre']) && ($lead->customer_name === 'Web Guest' || empty($lead->customer_name))) {
                        $updateData['customer_name'] = $extraction['nombre'];
                    }

                    $lead->update($updateData);
                }
            }

            return $replyContent;

        } catch (\Throwable $e) {
            Log::error("Chatbot Interaction Error: " . $e->getMessage());
            return "Disculpá, tuve una pequeña desconexión. ¿Me lo repetís?";
        }
    }
}

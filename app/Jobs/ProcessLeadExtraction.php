<?php

namespace App\Jobs;

use App\Models\Lead;
use App\Services\AiConciergeService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessLeadExtraction implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Lead $lead,
        public string $messageContent,
        public array $history = []
    ) {}

    /**
     * Execute the job.
     */
    public function handle(AiConciergeService $aiService): void
    {
        try {
            // Prepare context for extraction
            $extractionContext = $this->messageContent;
            if (! empty($this->history)) {
                $extractionContext = json_encode($this->history)."\nLAST_MSG: ".$this->messageContent;
            }

            // Call AI Service
            $extraction = $aiService->extractLeadData($extractionContext);

            if (! empty($extraction)) {
                $currentAiData = $this->lead->ai_data ?? [];

                // Merge new data
                $newAiData = array_merge($currentAiData, array_filter([
                    'destino' => $extraction['destino'] ?? null,
                    'presupuesto' => $extraction['presupuesto'] ?? null,
                    'pasajeros' => $extraction['pasajeros'] ?? null,
                ]));

                $updateData = [
                    'ai_data' => $newAiData,
                    'ai_summary' => $extraction['resumen'] ?? $this->lead->ai_summary,
                    'needs_human_attention' => ($extraction['requiere_atencion'] ?? false) || ($this->lead->needs_human_attention),
                ];

                // Update customer name if extracted and still generic
                if (! empty($extraction['nombre']) && ($this->lead->customer_name === 'Web Guest' || empty($this->lead->customer_name))) {
                    $updateData['customer_name'] = $extraction['nombre'];
                }

                $this->lead->update($updateData);
            }
        } catch (\Throwable $e) {
            Log::error('ProcessLeadExtraction Job Error: '.$e->getMessage());
        }
    }
}

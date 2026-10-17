<?php

namespace App\Jobs;

use App\Models\Lead;
use App\Services\AiConciergeService;
use App\Services\LeadEnrichmentService;
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

            app(LeadEnrichmentService::class)->apply($this->lead, $extraction);
        } catch (\Throwable $e) {
            Log::error('ProcessLeadExtraction Job Error: '.$e->getMessage());
        }
    }
}

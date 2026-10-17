<?php

namespace App\Jobs;

use App\Models\Lead;
use App\Services\AiConciergeService;
use App\Services\LeadEnrichmentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessAiResponse implements ShouldQueue
{
    use Queueable;

    public function __construct(public Lead $lead) {}

    /**
     * Extract structured data from the lead's original message and save it.
     */
    public function handle(AiConciergeService $aiService, LeadEnrichmentService $enrichment): void
    {
        $enrichment->apply($this->lead, $aiService->extractLeadData((string) $this->lead->raw_message));
    }
}

<?php

namespace App\Jobs;

use App\Models\Lead;
use App\Services\AiConciergeService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessAiResponse implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public Lead $lead)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(AiConciergeService $aiService): void
    {
        $aiService->extractLeadData($this->lead);
    }
}

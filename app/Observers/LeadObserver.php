<?php

namespace App\Observers;

use App\Mail\LeadReceivedMail;
use App\Models\Lead;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class LeadObserver
{
    /**
     * Handle the Lead "created" event.
     */
    public function created(Lead $lead): void
    {
        if (! $lead->customer?->email) {
            return;
        }

        // The mail is queued; a mail failure must never break saving the lead.
        try {
            Mail::to($lead->customer->email)->send(new LeadReceivedMail($lead));
        } catch (\Throwable $e) {
            Log::warning('LeadReceivedMail failed: '.$e->getMessage());
        }
    }
}

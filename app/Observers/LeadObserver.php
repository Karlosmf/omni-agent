<?php

namespace App\Observers;

use App\Enums\LeadStatus;
use App\Mail\LeadReceivedMail;
use App\Models\Lead;
use App\Services\LeadAssignmentService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class LeadObserver
{
    public function __construct(private LeadAssignmentService $assignment) {}

    /**
     * Handle the Lead "created" event.
     */
    public function created(Lead $lead): void
    {
        $this->assignment->assign($lead);

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

    /**
     * Moving a lead out of "new" counts as contacting it.
     */
    public function updated(Lead $lead): void
    {
        if ($lead->wasChanged('status') && $lead->status !== LeadStatus::New && ! $lead->last_contacted_at) {
            $lead->forceFill(['last_contacted_at' => now()])->saveQuietly();
        }
    }
}

<?php

namespace App\Services;

use App\Enums\LeadStatus;
use App\Enums\UserRole;
use App\Models\Lead;
use App\Models\User;

/**
 * Optional automatic assignment: each new lead goes to the sales agent with the fewest open leads.
 */
class LeadAssignmentService
{
    public function assign(Lead $lead): ?User
    {
        if (! get_agency_settings()?->auto_assign_leads || $lead->agent_id) {
            return null;
        }

        $agent = User::where('role', UserRole::Sales)
            ->withCount(['leadsAssigned as open_leads_count' => fn ($q) => $q->where('status', LeadStatus::New)])
            ->orderBy('open_leads_count')
            ->orderBy('id')
            ->first();

        if ($agent) {
            $lead->forceFill(['agent_id' => $agent->id])->saveQuietly();
        }

        return $agent;
    }
}

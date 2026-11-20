<?php

namespace App\Services;

use App\Enums\Channel;
use App\Enums\LeadStatus;
use App\Enums\LeadTemperature;
use App\Models\Lead;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;

/**
 * Alerts the team when a hot lead (or one that asked for a person) waits too long for a human answer.
 * The AI answers instantly, so the clock that matters is the first reply from a person.
 */
class SlaService
{
    /**
     * @return Collection<int, Lead>
     */
    public function overdue(): Collection
    {
        $minutes = (int) get_agency_settings()?->sla_first_response_minutes;

        if ($minutes <= 0) {
            return new Collection;
        }

        return Lead::query()
            ->whereIn('channel', array_map(fn (Channel $c) => $c->value, Channel::inbox()))
            ->whereNull('first_response_at')
            ->whereNull('sla_alerted_at')
            ->where('status', '!=', LeadStatus::Closed)
            ->where('last_inbound_at', '<=', now()->subMinutes($minutes))
            ->where(fn ($q) => $q->where('temperature', LeadTemperature::Hot)->orWhere('needs_human_attention', true))
            ->with(['customer', 'agent'])
            ->get();
    }

    /**
     * Notify once per lead. Returns how many leads were alerted.
     */
    public function alertOverdue(): int
    {
        $leads = $this->overdue();

        foreach ($leads as $lead) {
            $recipients = $lead->staffToNotify();
            $name = $lead->displayName();
            $waiting = (int) $lead->last_inbound_at->diffInMinutes(now(), true);

            foreach ($recipients as $user) {
                Notification::make()
                    ->title('Cliente esperando respuesta')
                    ->body("{$name} escribió por {$lead->conversationChannel()->getLabel()} hace {$waiting} min y todavía no lo atendió una persona.")
                    ->danger()
                    ->sendToDatabase($user);
            }

            $lead->forceFill(['sla_alerted_at' => now()])->saveQuietly();
        }

        return $leads->count();
    }
}

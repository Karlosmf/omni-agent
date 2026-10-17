<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\FollowUpAction;
use App\Enums\FollowUpTrigger;
use App\Enums\LeadStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\FollowUpRule;
use App\Models\FollowUpRun;
use App\Models\Lead;
use App\Models\Task;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Evaluates the configurable follow-up rules. Each rule fires once per lead/booking.
 */
class FollowUpService
{
    public const VIEWS_THRESHOLD = 3;

    /**
     * Run every active rule. Returns how many times each rule fired, keyed by rule id.
     *
     * @return array<int, int>
     */
    public function run(): array
    {
        $fired = [];

        foreach (FollowUpRule::where('is_active', true)->get() as $rule) {
            $fired[$rule->id] = 0;

            foreach ($this->subjectsFor($rule) as $subject) {
                $run = FollowUpRun::firstOrCreate([
                    'follow_up_rule_id' => $rule->id,
                    'subject_type' => $subject->getMorphClass(),
                    'subject_id' => $subject->getKey(),
                ]);

                if (! $run->wasRecentlyCreated) {
                    continue;
                }

                $this->perform($rule, $subject);
                $fired[$rule->id]++;
            }
        }

        return $fired;
    }

    /**
     * @return Collection<int, Lead|Booking>
     */
    public function subjectsFor(FollowUpRule $rule): Collection
    {
        $limit = now()->subHours($rule->delay_hours);

        return match ($rule->trigger) {
            FollowUpTrigger::LeadUnassigned => $this->openLeads()
                ->whereNull('agent_id')
                ->where('created_at', '<=', $limit)
                ->get(),

            FollowUpTrigger::LeadNotContacted => $this->openLeads()
                ->whereNull('last_contacted_at')
                ->where('created_at', '<=', $limit)
                ->get(),

            FollowUpTrigger::LeadHot => $this->openLeads()
                ->whereNull('last_contacted_at')
                ->where(fn (Builder $q) => $q->where('needs_human_attention', true)->orWhere('temperature', 'hot'))
                ->where('updated_at', '<=', $limit)
                ->get(),

            FollowUpTrigger::BookingNotOpened => $this->quotedBookings()
                ->where('view_count', 0)
                ->whereHas('activities', fn (Builder $q) => $q->where('type', 'email_sent')->where('created_at', '<=', $limit))
                ->get(),

            FollowUpTrigger::BookingViewedOften => $this->quotedBookings()
                ->where('view_count', '>=', self::VIEWS_THRESHOLD)
                ->where('last_viewed_at', '<=', $limit)
                ->get(),

            FollowUpTrigger::BookingExpired => $this->quotedBookings()
                ->whereNotNull('valid_until')
                ->whereDate('valid_until', '<', today())
                ->get(),
        };
    }

    private function openLeads(): Builder
    {
        return Lead::query()->where('status', LeadStatus::New)->with(['agent', 'customer']);
    }

    private function quotedBookings(): Builder
    {
        return Booking::query()->whereIn('status', [BookingStatus::Borrador, BookingStatus::Presupuesto])->with(['agent', 'customer']);
    }

    private function perform(FollowUpRule $rule, Model $subject): void
    {
        [$title, $body] = $this->messageFor($rule->trigger, $subject);
        $agent = $subject->agent;

        match ($rule->action) {
            FollowUpAction::CreateTask => $this->createTask($subject, $agent, $title, $body),
            FollowUpAction::NotifyAgent => $this->notify($agent ? collect([$agent]) : $this->admins(), $title, $body),
            FollowUpAction::NotifyAdmins => $this->notify($this->admins(), $title, $body),
        };
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function messageFor(FollowUpTrigger $trigger, Model $subject): array
    {
        $who = $subject instanceof Lead
            ? ($subject->customer_name ?: $subject->customer?->name ?: "Lead #{$subject->id}")
            : "{$subject->file_number} ({$subject->holder_name})";

        return match ($trigger) {
            FollowUpTrigger::LeadUnassigned => ['Lead sin agente asignado', "{$who} todavía no tiene un agente."],
            FollowUpTrigger::LeadNotContacted => ["Contactar a {$who}", "El lead sigue sin contactar. Origen: {$subject->source}."],
            FollowUpTrigger::LeadHot => ["Urgente: {$who} pide hablar con una persona", 'El cliente pidió atención humana.'],
            FollowUpTrigger::BookingNotOpened => ["Llamar a {$who}", 'Enviaste el presupuesto y el cliente todavía no lo abrió.'],
            FollowUpTrigger::BookingViewedOften => ["Cerrar la venta con {$who}", 'El cliente abrió el presupuesto '.$subject->view_count.' veces y todavía no señó.'],
            FollowUpTrigger::BookingExpired => ["Presupuesto vencido: {$who}", 'Podés renovar la validez desde el expediente.'],
        };
    }

    private function createTask(Model $subject, ?User $agent, string $title, string $body): void
    {
        $assignee = $agent ?? $this->admins()->first();

        if (! $assignee) {
            return;
        }

        Task::create([
            'agent_id' => $assignee->id,
            'title' => $title,
            'description' => $body,
            'due_date' => today(),
            'is_completed' => false,
            'related_type' => $subject->getMorphClass(),
            'related_id' => $subject->getKey(),
        ]);

        if ($subject instanceof Lead) {
            $subject->forceFill(['next_follow_up_at' => today()->endOfDay()])->saveQuietly();
        }
    }

    /**
     * @param  iterable<int, User>  $users
     */
    private function notify(iterable $users, string $title, string $body): void
    {
        foreach ($users as $user) {
            Notification::make()->title($title)->body($body)->warning()->sendToDatabase($user);
        }
    }

    /**
     * @return Collection<int, User>
     */
    private function admins(): Collection
    {
        return User::where('role', UserRole::Admin)->get();
    }
}

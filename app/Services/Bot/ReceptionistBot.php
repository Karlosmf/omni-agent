<?php

namespace App\Services\Bot;

use App\Ai\AgencyAi;
use App\Ai\Agents\ReceptionistAgent;
use App\Enums\BotNoticeType;
use App\Enums\LeadStatus;
use App\Enums\LeadTemperature;
use App\Models\BotNotice;
use App\Models\Lead;
use App\Models\Message;
use App\Services\AiConciergeService;
use App\Services\LeadEnrichmentService;
use App\Support\KeywordMatcher;
use App\Support\PhoneNumber;
use App\Support\ReplyButtons;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Decides what the receptionist answers to a customer message: picks the inquiry, asks the AI, learns the data and raises
 * notices. It never sends anything: the conversation service delivers the reply, and only ever in answer to an inbound message.
 */
class ReceptionistBot
{
    public function __construct(
        private AiConciergeService $ai,
        private AgencyAi $agencyAi,
        private ReplyGuard $guard,
        private LeadEnrichmentService $enrichment,
    ) {}

    public static function isEnabled(): bool
    {
        return (bool) get_agency_settings()?->bot_enabled;
    }

    /**
     * When a person answers, the bot steps back from every open inquiry of that conversation. A pause set by hand has no end and stays that way.
     *
     * @return array{ai_paused: true, ai_paused_until: ?Carbon}
     */
    public static function pauseAfterHumanReply(Lead $lead): array
    {
        $manualPause = $lead->ai_paused && $lead->ai_paused_until === null;
        $until = self::isEnabled() && ! $manualPause ? now()->addHours(self::pauseHours()) : null;

        if (self::isEnabled() && $lead->customer_id) {
            Lead::where('customer_id', $lead->customer_id)->whereKeyNot($lead->id)->where('channel', $lead->channel)->open()
                ->update(['ai_paused' => true, 'ai_paused_until' => now()->addHours(self::pauseHours())]);
        }

        return ['ai_paused' => true, 'ai_paused_until' => $until];
    }

    /**
     * The team wrote from the phone in a chat that has no inquiry yet: if the customer answers, the bot must not jump in.
     */
    public static function rememberHumanChat(string $phone, Carbon $sentAt): void
    {
        $until = $sentAt->copy()->addHours(self::pauseHours());

        if (self::isEnabled() && $until->isFuture()) {
            Cache::put(self::humanChatKey($phone), true, $until);
        }
    }

    public function respond(Lead $lead, Message $inbound, bool $isText): ?BotReply
    {
        if ($this->isPaused($lead)) {
            return null;
        }

        if ($this->reachedDailyCap($lead)) {
            BotNotice::raise($lead, BotNoticeType::Handoff, 'El bot llegó a su tope de respuestas de hoy en esta conversación.');

            return null;
        }

        if (! $isText) {
            return $this->handOff($lead, 'El cliente envió algo que el bot no puede leer: '.Str::limit((string) $inbound->content, 80),
                'Por ahora solo puedo leer mensajes de texto, ¿me lo escribís? Igual ya le aviso a '.$this->advisors().'.');
        }

        // The team's own words for "I want a person": answered at once, without spending the AI.
        if ($keyword = $this->handoffKeywordIn((string) $inbound->content)) {
            return $this->handOff($lead, "El cliente pidió hablar con una persona (escribió «{$keyword}»).");
        }

        if (! $this->agencyAi->isEnabled()) {
            return $this->handOff($lead, 'La IA está desactivada: el bot no pudo responder esta consulta.');
        }

        if ($this->ai->budgetExhausted()) {
            $this->ai->alertBudgetExhausted();

            return $this->handOff($lead, 'Se alcanzó el límite mensual de la IA.');
        }

        try {
            return $this->answer($lead, $inbound);
        } catch (Throwable $e) {
            Log::error("ReceptionistBot failed for lead {$lead->id}: ".Str::limit($e->getMessage(), 200));

            return $this->handOff($lead, 'La IA no pudo responder este mensaje.');
        }
    }

    /**
     * The internal test chat: same agent and same guard, but nothing is stored, sent or noticed.
     *
     * @param  list<array{role: string, content: string}>  $history
     * @param  array<string, mixed>  $trip
     * @return array{reply: string, trip: array<string, mixed>, handoff: ?string, buttons: list<string>}
     */
    public function preview(array $history, array $trip, string $text): array
    {
        if ($keyword = $this->handoffKeywordIn($text)) {
            return ['reply' => $this->handoffText(), 'trip' => $trip, 'handoff' => "El cliente pidió hablar con una persona (escribió «{$keyword}»).", 'buttons' => []];
        }

        $response = $this->ai->prompt(new ReceptionistAgent(new BotContext($trip, history: $history)), $text);
        $turn = $response->toArray();
        $reply = trim((string) ($turn['reply'] ?? ''));
        $customerText = collect($history)->where('role', 'user')->pluck('content')->implode(' ').' '.$text;

        if (! $this->isSafe($reply, $customerText)) {
            return ['reply' => $this->handoffText(), 'trip' => $trip, 'handoff' => 'La respuesta de la IA fue bloqueada: incluía pagos o precios.', 'buttons' => []];
        }

        return [
            'reply' => $reply,
            'trip' => ($turn['consulta_accion'] ?? 'continuar') === 'aclarar' ? $trip : TripData::merge($trip, $turn),
            'handoff' => ! empty($turn['derivar']) ? ($turn['motivo_derivacion'] ?? 'Derivación') : null,
            'buttons' => $this->buttonsOf($turn),
        ];
    }

    private function answer(Lead $lead, Message $inbound): BotReply
    {
        $open = $this->openInquiries($lead, $inbound);
        $active = $open->first();
        $trip = TripData::of($active);

        $response = $this->ai->prompt(new ReceptionistAgent(new BotContext(
            trip: $trip,
            profile: $active->customer?->traveler_profile ?? [],
            openInquiries: $open->map(fn (Lead $inquiry) => [
                'id' => $inquiry->id,
                'destino' => TripData::of($inquiry)['destino'],
                'fechas' => TripData::toLeadData(TripData::of($inquiry))['fecha'] ?? null,
                'activa' => $inquiry->id === $active->id,
            ])->values()->all(),
            history: $this->historyOf($active, $inbound),
            contactName: $active->customer_name ?: $active->customer?->name,
        )), (string) $inbound->content);

        $turn = $response->toArray();
        $text = trim((string) ($turn['reply'] ?? ''));

        if ($text === '') {
            throw new \RuntimeException('AI_EMPTY_REPLY');
        }

        $target = $this->resolveInquiry($active, $open, $trip, $turn);

        if ($target->id !== $lead->id || $inbound->lead_id !== $target->id) {
            $this->moveInbound($inbound, $lead, $target);
        }

        if (($turn['consulta_accion'] ?? 'continuar') !== 'aclarar') {
            $this->learn($target, $turn);
        }

        $customerText = $target->messages()->where('role', 'user')->newestFirst()->limit(30)->pluck('content')->implode(' ');

        if (! $this->isSafe($text, $customerText)) {
            Log::warning("ReceptionistBot reply blocked for lead {$target->id}");
            $turn['derivar'] = true;
            $turn['motivo_derivacion'] = 'El cliente preguntó por pagos o precios: la IA intentó responder y se bloqueó.';
            $text = $this->handoffText();
        }

        if (! empty($turn['derivar'])) {
            $this->raiseHandoff($target, $turn['motivo_derivacion'] ?? null);
        }

        return new BotReply($target, $text, $response->usage->inputTokens, $response->usage->outputTokens, $response->meta->model ?? null, $this->buttonsOf($turn));
    }

    /**
     * The closed answers the AI offered for its question, as reply buttons. A handoff never carries buttons.
     *
     * @param  array<string, mixed>  $turn
     * @return list<string>
     */
    private function buttonsOf(array $turn): array
    {
        return ! empty($turn['derivar']) ? [] : ReplyButtons::normalize((array) ($turn['opciones'] ?? []));
    }

    /**
     * Open inquiries of this conversation (never another channel: the reply goes out through the inquiry's channel).
     * The active one comes first: where the customer talked last, not counting the message being answered.
     *
     * @return Collection<int, Lead>
     */
    private function openInquiries(Lead $lead, Message $inbound): Collection
    {
        $channel = $lead->conversationChannel();

        $open = Lead::where('customer_id', $lead->customer_id)->open()
            ->with('customer')
            ->withMax(['messages as last_message_id' => fn ($query) => $query->whereKeyNot($inbound->id)], 'id')
            ->orderByDesc('last_message_id')
            ->orderByDesc('id')
            ->get()
            ->filter(fn (Lead $inquiry) => $inquiry->conversationChannel() === $channel)
            ->values();

        if (! $open->contains('id', $lead->id)) {
            $open->prepend($lead);
        }

        return $open;
    }

    /**
     * @param  Collection<int, Lead>  $open
     * @param  array<string, mixed>  $trip
     * @param  array<string, mixed>  $turn
     */
    private function resolveInquiry(Lead $active, Collection $open, array $trip, array $turn): Lead
    {
        $action = $turn['consulta_accion'] ?? 'continuar';

        if ($action === 'nueva' && $this->isReallyNew($trip, $turn)) {
            return $this->createPendingInquiry($active);
        }

        return $action === 'continuar' ? ($open->firstWhere('id', $turn['consulta_id'] ?? 0) ?? $active) : $active;
    }

    /**
     * An inquiry with no destination yet IS the new trip, and the same destination is the same trip.
     *
     * @param  array<string, mixed>  $trip
     * @param  array<string, mixed>  $turn
     */
    private function isReallyNew(array $trip, array $turn): bool
    {
        return filled($trip['destino']) && mb_strtolower(trim((string) ($turn['destino'] ?? ''))) !== mb_strtolower(trim($trip['destino']));
    }

    /**
     * A second trip of a known contact. Created without events so the customer gets no "we received your inquiry" email
     * for something the team has not confirmed yet, and it stays with the same advisor.
     */
    private function createPendingInquiry(Lead $from): Lead
    {
        $pending = Lead::withoutEvents(fn () => Lead::create([
            'customer_id' => $from->customer_id,
            'customer_name' => $from->customer_name,
            'agent_id' => $from->agent_id,
            'source' => $from->source,
            'status' => LeadStatus::PendingReview,
            'temperature' => LeadTemperature::Cool,
            'raw_message' => '',
            'ai_data' => [],
            'needs_human_attention' => false,
            'channel' => $from->channel,
            'channel_user_id' => $from->channel_user_id,
            'channel_account_id' => $from->channel_account_id,
        ]));

        BotNotice::raise($pending, BotNoticeType::VerifyNewInquiry);

        return $pending;
    }

    private function moveInbound(Message $inbound, Lead $from, Lead $target): void
    {
        $inbound->update(['lead_id' => $target->id]);

        $from->forceFill(['unread_count' => max(0, $from->unread_count - 1)])->saveQuietly();

        $target->forceFill([
            'unread_count' => $target->unread_count + 1,
            'last_inbound_at' => now(),
            'whatsapp_last_inbound_at' => $from->whatsapp_last_inbound_at,
            'raw_message' => $target->raw_message ?: (string) $inbound->content,
        ])->saveQuietly();
    }

    /**
     * @param  array<string, mixed>  $turn
     */
    private function learn(Lead $lead, array $turn): void
    {
        $trip = TripData::merge(TripData::of($lead), $turn);

        $lead->forceFill(['trip_data' => $trip])->saveQuietly();

        $this->enrichment->apply($lead, [
            ...TripData::toLeadData($trip),
            'nombre' => $turn['nombre'] ?? null,
            'resumen' => $turn['resumen'] ?? null,
        ]);

        $this->rememberOnContact($lead, $trip);

        $alreadyNoticed = $lead->botNotices()->where('type', BotNoticeType::DataComplete)->exists();
        $nothingLeftToAsk = TripData::isComplete($trip) || (! empty($turn['sin_mas_datos']) && filled($trip['destino']));

        if ($nothingLeftToAsk && ! $alreadyNoticed) {
            BotNotice::raise($lead, BotNoticeType::DataComplete);
        }
    }

    /**
     * What stays true across trips, so the next inquiry only has to confirm it.
     *
     * @param  array<string, mixed>  $trip
     */
    private function rememberOnContact(Lead $lead, array $trip): void
    {
        if (! $lead->customer) {
            return;
        }

        $known = array_filter([
            'pasaporte' => $trip['documentacion']['pasaporte'],
            'visa' => $trip['documentacion']['visa'],
            'edades_menores' => $trip['pasajeros']['edades_menores'],
            'ciudad_salida' => $trip['ciudad_salida'],
        ], fn ($value) => $value !== null);

        if ($known !== []) {
            $lead->customer->forceFill(['traveler_profile' => array_merge($lead->customer->traveler_profile ?? [], $known)])->saveQuietly();
        }
    }

    /**
     * @return list<array{role: string, content: string}>
     */
    private function historyOf(Lead $lead, Message $inbound): array
    {
        return $lead->messages()
            ->whereKeyNot($inbound->id)
            ->newestFirst()
            ->limit(15)
            ->get()
            ->reverse()
            ->map(fn (Message $message) => ['role' => $message->role === 'user' ? 'user' : 'assistant', 'content' => (string) $message->content])
            ->values()
            ->all();
    }

    private function handoffKeywordIn(string $text): ?string
    {
        return KeywordMatcher::longestMatch($text, KeywordMatcher::parse(get_agency_settings()?->bot_handoff_keywords));
    }

    private function isPaused(Lead $lead): bool
    {
        return self::isPersonAttending($lead);
    }

    /**
     * A person is handling the conversation (paused by hand, after a human reply, or a chat the team started from the phone):
     * nothing automatic answers, neither the bot nor the chat menus.
     */
    public static function isPersonAttending(Lead $lead): bool
    {
        if ($lead->isAiPaused()) {
            return true;
        }

        $phone = PhoneNumber::toE164($lead->customer?->phone);

        return $phone !== null && Cache::has(self::humanChatKey($phone));
    }

    /**
     * Counted across the contact's inquiries, so opening a second one does not reset the cap.
     */
    private function reachedDailyCap(Lead $lead): bool
    {
        $limit = (int) (get_agency_settings()?->bot_daily_reply_limit ?? 0);

        return $limit > 0 && Message::where('sent_from', Message::SENT_FROM_BOT)
            ->where('created_at', '>=', today())
            ->whereHas('lead', fn ($query) => $query->where('customer_id', $lead->customer_id))
            ->count() >= $limit;
    }

    private function isSafe(string $reply, string $customerText): bool
    {
        $promotions = collect(get_agency_settings()?->currentPromotions() ?? [])
            ->map(fn (array $promotion) => $promotion['title'].' '.$promotion['text'])
            ->implode(' ');

        return $this->guard->isSafe($reply, $promotions, $customerText);
    }

    /**
     * The bot always answers: when it cannot help, the customer hears that a person will write and the team gets a notice.
     */
    private function handOff(Lead $lead, string $reason, ?string $text = null): BotReply
    {
        $this->raiseHandoff($lead, $reason);

        return new BotReply($lead, $text ?? $this->handoffText());
    }

    private function raiseHandoff(Lead $lead, ?string $reason): void
    {
        BotNotice::raise($lead, BotNoticeType::Handoff, $reason ? Str::limit($reason, 250) : null);

        $lead->forceFill(['needs_human_attention' => true, 'temperature' => LeadTemperature::Hot])->saveQuietly();
    }

    private function handoffText(): string
    {
        return '¡Gracias por escribirnos! '.$this->advisors().' se van a comunicar con vos para ayudarte con eso. 😊';
    }

    private function advisors(): string
    {
        return advisor_names(get_agency_settings(), 'Nuestros asesores');
    }

    private static function pauseHours(): int
    {
        return max(1, (int) (get_agency_settings()?->bot_pause_hours ?? 12));
    }

    private static function humanChatKey(string $phone): string
    {
        return 'bot-human-chat:'.$phone;
    }
}

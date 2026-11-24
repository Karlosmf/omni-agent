<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\Channel;
use App\Enums\LeadStatus;
use App\Enums\LeadTemperature;
use App\Enums\UserRole;
use App\Observers\LeadObserver;
use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[ObservedBy(LeadObserver::class)]
class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory;

    /** Sin 0/O ni 1/I: el cliente puede tener que leerlo. */
    private const LINK_CODE_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    protected $fillable = [
        'customer_id',
        'customer_name',
        'agent_id',
        'source',
        'travel_package_id',
        'status',
        'customer_budget',
        'raw_message',
        'ai_data',
        'trip_data',
        'ai_summary',
        'needs_human_attention',
        'temperature',
        'last_contacted_at',
        'next_follow_up_at',
        'ai_paused',
        'ai_paused_until',
        'whatsapp_last_inbound_at',
        'unread_count',
        'channel',
        'channel_user_id',
        'channel_account_id',
        'last_inbound_at',
        'first_response_at',
        'sla_alerted_at',
        'attribution',
        'utm_source',
        'utm_medium',
        'utm_campaign',
    ];

    protected $casts = [
        'status' => LeadStatus::class,
        'temperature' => LeadTemperature::class,
        'ai_data' => 'array',
        'trip_data' => 'array',
        'needs_human_attention' => 'boolean',
        'last_contacted_at' => 'datetime',
        'next_follow_up_at' => 'datetime',
        'ai_paused' => 'boolean',
        'ai_paused_until' => 'datetime',
        'whatsapp_last_inbound_at' => 'datetime',
        'last_inbound_at' => 'datetime',
        'first_response_at' => 'datetime',
        'sla_alerted_at' => 'datetime',
        'attribution' => 'array',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function booking(): HasOne
    {
        return $this->hasOne(Booking::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * The funnel stage its bookings put it in: the most advanced one wins. Null when it has no bookings.
     */
    public function stageFromBookings(): ?LeadStatus
    {
        $statuses = $this->bookings()->pluck('status')->map(fn ($status) => $status instanceof BookingStatus ? $status : BookingStatus::from($status));

        return match (true) {
            $statuses->isEmpty() => null,
            $statuses->contains(fn (BookingStatus $s) => in_array($s, [BookingStatus::Senado, BookingStatus::Emitido], true)) => LeadStatus::Booked,
            $statuses->contains(fn (BookingStatus $s) => in_array($s, [BookingStatus::Borrador, BookingStatus::Presupuesto], true)) => LeadStatus::Quoted,
            default => LeadStatus::Closed,
        };
    }

    /**
     * Moves the inquiry to the stage of its bookings, so nobody has to drag it by hand. A lead without bookings is left alone.
     */
    public function syncStageWithBookings(): void
    {
        $stage = $this->stageFromBookings();

        if ($stage !== null && $stage !== $this->status) {
            $this->update(['status' => $stage]);
        }
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function botNotices(): HasMany
    {
        return $this->hasMany(BotNotice::class);
    }

    /**
     * What a non-admin may see: their own inquiries and the unassigned ones. Admins see everything.
     *
     * @param  Builder<self>  $query
     */
    public function scopeVisibleTo(Builder $query, ?User $user): void
    {
        if ($user && ! $user->isAdmin()) {
            $query->where(fn (Builder $q) => $q->where('agent_id', $user->id)->orWhereNull('agent_id'));
        }
    }

    /**
     * Inquiries still being worked on.
     *
     * @param  Builder<self>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereNotIn('status', [LeadStatus::Closed, LeadStatus::Booked]);
    }

    /**
     * Sold inquiries: dragged to "Reservada" or with a booking that was paid a deposit or issued.
     *
     * @param  Builder<self>  $query
     */
    public function scopeWon(Builder $query): void
    {
        $query->where(fn (Builder $q) => $q
            ->where('status', LeadStatus::Booked)
            ->orWhereHas('bookings', fn (Builder $bookings) => $bookings->whereIn('status', BookingStatus::confirmed())));
    }

    /**
     * Inquiries closed without a sale.
     *
     * @param  Builder<self>  $query
     */
    public function scopeLost(Builder $query): void
    {
        $query->where('status', LeadStatus::Closed)
            ->whereDoesntHave('bookings', fn (Builder $bookings) => $bookings->whereIn('status', BookingStatus::confirmed()));
    }

    /**
     * Inquiries still being worked on that have not been sold yet: the ones to follow up.
     *
     * @param  Builder<self>  $query
     */
    public function scopePendingOutcome(Builder $query): void
    {
        $query->open()
            ->whereDoesntHave('bookings', fn (Builder $bookings) => $bookings->whereIn('status', BookingStatus::confirmed()));
    }

    /**
     * Human label for where the inquiry came from.
     */
    public static function sourceLabel(?string $source): string
    {
        return match ($source) {
            'chatbot' => 'Chatbot IA',
            'web_form' => 'Formulario Web / Catálogo',
            'cotizador_publico' => 'Cotizador web',
            'whatsapp' => 'WhatsApp Directo',
            'instagram' => 'Instagram',
            'messenger' => 'Messenger',
            'manual' => 'Manual (Agente)',
            null, '' => 'Desconocido',
            default => ucfirst($source),
        };
    }

    /**
     * Most urgent first: someone asking for a person, then an overdue follow-up, then hot leads,
     * then the nearest follow-up (leads without one last), then the latest customer message.
     *
     * @param  Builder<self>  $query
     */
    public function scopeOrderByPriority(Builder $query): void
    {
        $now = now()->toDateTimeString();

        $query
            ->orderByDesc('needs_human_attention')
            ->orderByRaw('CASE WHEN next_follow_up_at IS NOT NULL AND next_follow_up_at < ? THEN 0 ELSE 1 END', [$now])
            ->orderByRaw('CASE temperature WHEN ? THEN 0 WHEN ? THEN 1 ELSE 2 END', [LeadTemperature::Hot->value, LeadTemperature::Warm->value])
            ->orderByRaw('CASE WHEN next_follow_up_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('next_follow_up_at')
            ->orderByDesc('last_inbound_at')
            ->orderByDesc('id');
    }

    /**
     * Open inquiries someone has to act on today: asking for a person, still new, or with a follow-up due by tonight.
     *
     * @param  Builder<self>  $query
     */
    public function scopeNeedsActionToday(Builder $query): void
    {
        $query->open()->where(fn (Builder $q) => $q
            ->where('needs_human_attention', true)
            ->orWhere('status', LeadStatus::New)
            ->orWhere('next_follow_up_at', '<=', now()->endOfDay()));
    }

    /**
     * Open inquiries whose follow-up date already passed.
     *
     * @param  Builder<self>  $query
     */
    public function scopeFollowUpOverdue(Builder $query): void
    {
        $query->open()->where('next_follow_up_at', '<', now());
    }

    public function isFollowUpOverdue(): bool
    {
        return $this->next_follow_up_at?->isPast() && ! in_array($this->status, [LeadStatus::Closed, LeadStatus::Booked], true);
    }

    /**
     * The name the team sees for this inquiry: the contact name, the customer's name, or its number.
     */
    public function displayName(): string
    {
        return $this->customer_name ?: $this->customer?->name ?: "Consulta #{$this->id}";
    }

    /**
     * Who hears about this inquiry: its agent, or every admin while nobody has it.
     *
     * @return Collection<int, User>
     */
    public function staffToNotify(): Collection
    {
        return $this->agent ? new Collection([$this->agent]) : User::where('role', UserRole::Admin)->get();
    }

    /**
     * The destination the customer asked about, from the bot's trip data or the older AI data.
     */
    public function destination(): ?string
    {
        return $this->trip_data['destino'] ?? $this->ai_data['destino'] ?? null;
    }

    /**
     * Whether the AI must stay quiet: paused by hand (no end) or after a person answered (until the pause runs out).
     */
    public function isAiPaused(): bool
    {
        return $this->ai_paused && ($this->ai_paused_until === null || $this->ai_paused_until->isFuture());
    }

    public function channelAccount(): BelongsTo
    {
        return $this->belongsTo(ChannelAccount::class);
    }

    /**
     * The channel this conversation lives in. Leads created before channels existed fall back to their source.
     */
    public function conversationChannel(): Channel
    {
        return Channel::tryFrom((string) ($this->channel ?? $this->source)) ?? Channel::Web;
    }

    /**
     * Meta only allows free-form replies within 24 hours of the customer's last message.
     * WhatsApp keeps its own legacy column; the social channels use last_inbound_at.
     */
    public function isInsideReplyWindow(): bool
    {
        $channel = $this->conversationChannel();
        $lastInbound = $channel === Channel::WhatsApp ? $this->whatsapp_last_inbound_at : $this->last_inbound_at;

        return $lastInbound?->gt(now()->subHours($channel->windowHours())) ?? false;
    }

    /**
     * Past 24 h a person (never the AI) may still answer on Instagram/Messenger for a few days
     * with Meta's HUMAN_AGENT tag, if the account allows it.
     */
    public function canReplyAsHumanAgent(): bool
    {
        $channel = $this->conversationChannel();

        return $channel->isSocial()
            && (bool) $this->channelAccount?->allow_human_agent
            && ($this->last_inbound_at?->gt(now()->subDays($channel->humanAgentDays())) ?? false);
    }

    /**
     * Código de un solo uso para seguir este chat web en WhatsApp, Instagram o Messenger.
     */
    public function ensureChannelLinkCode(): string
    {
        if (! $this->channel_link_code) {
            do {
                $code = 'V-'.collect(range(1, 6))->map(fn () => self::LINK_CODE_ALPHABET[random_int(0, strlen(self::LINK_CODE_ALPHABET) - 1)])->implode('');
            } while (static::where('channel_link_code', $code)->exists());

            $this->forceFill(['channel_link_code' => $code])->saveQuietly();
        }

        return $this->channel_link_code;
    }

    /**
     * El lead web abierto y reciente cuyo código aparece en el texto o en el ref del enlace.
     */
    public static function findByLinkCode(?string $text): ?self
    {
        if (! $text || ! preg_match('/\bV-(['.self::LINK_CODE_ALPHABET.']{6})\b/i', $text, $match)) {
            return null;
        }

        return static::where('channel_link_code', 'V-'.strtoupper($match[1]))
            ->where('status', '!=', LeadStatus::Closed)
            ->where('created_at', '>=', now()->subDays(14))
            ->first();
    }

    public function isInsideWhatsAppWindow(): bool
    {
        return $this->isInsideReplyWindow();
    }

    public function travelPackage(): BelongsTo
    {
        return $this->belongsTo(TravelPackage::class);
    }
}

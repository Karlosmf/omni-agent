<?php

namespace App\Models;

use App\Enums\Channel;
use App\Enums\LeadStatus;
use App\Enums\LeadTemperature;
use App\Observers\LeadObserver;
use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
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
        'ai_summary',
        'needs_human_attention',
        'temperature',
        'last_contacted_at',
        'next_follow_up_at',
        'ai_paused',
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
        'needs_human_attention' => 'boolean',
        'last_contacted_at' => 'datetime',
        'next_follow_up_at' => 'datetime',
        'ai_paused' => 'boolean',
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

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
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

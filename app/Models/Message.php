<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    //
    protected $fillable = [
        'lead_id',
        'role',
        'content',
        'channel',
        'direction',
        'external_id',
        'status',
        'sent_by_user_id',
        'sent_from',
        'media_path',
        'input_tokens',
        'output_tokens',
        'ai_model',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    protected $hidden = ['payload'];

    /**
     * Sent from the WhatsApp Business app on the agency's phone (coexistence).
     */
    public const SENT_FROM_PHONE = 'phone';

    /**
     * Written by the receptionist bot (counts towards its daily cap).
     */
    public const SENT_FROM_BOT = 'bot';

    /**
     * A chat menu (list message) or its fixed answer. No AI is involved, so it does not count towards the bot's daily cap.
     */
    public const SENT_FROM_MENU = 'menu';

    /**
     * Sent through Zernio by something other than this app or the phone (Zernio's dashboard, another integration).
     * Only appears in imported history.
     */
    public const SENT_FROM_ZERNIO = 'zernio';

    /**
     * In the order things were said. Imported history gets new ids for old messages, so the date decides.
     *
     * @param  Builder<self>  $query
     */
    public function scopeChronological(Builder $query): void
    {
        $query->orderBy('created_at')->orderBy('id');
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeNewestFirst(Builder $query): void
    {
        $query->orderByDesc('created_at')->orderByDesc('id');
    }

    /**
     * Brought in later from the provider's history, not received live.
     */
    public function isImported(): bool
    {
        return (bool) ($this->payload['imported'] ?? false);
    }

    /**
     * Who wrote an outgoing message, as the inbox shows it.
     */
    public function authorLabel(): string
    {
        return match (true) {
            $this->sent_from === self::SENT_FROM_PHONE => 'Celular',
            $this->sent_from === self::SENT_FROM_ZERNIO => 'Zernio',
            $this->sent_from === self::SENT_FROM_MENU => 'Menú',
            $this->sent_by_user_id !== null => 'Equipo',
            default => 'IA',
        };
    }

    /**
     * The reply buttons sent under this message, or the options of a list message, if any.
     *
     * @return list<string>
     */
    public function buttons(): array
    {
        return array_values(array_filter((array) ($this->payload['buttons'] ?? $this->payload['list'] ?? []), 'is_string'));
    }

    /**
     * The document sent with this message, if any.
     *
     * @return array{url: string, name: string}|null
     */
    public function attachment(): ?array
    {
        $attachment = $this->payload['attachment'] ?? null;

        return is_array($attachment) && isset($attachment['url'], $attachment['name'])
            ? ['url' => (string) $attachment['url'], 'name' => (string) $attachment['name']]
            : null;
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}

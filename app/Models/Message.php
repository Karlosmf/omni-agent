<?php

namespace App\Models;

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
    ];

    /**
     * Sent from the WhatsApp Business app on the agency's phone (coexistence).
     */
    public const SENT_FROM_PHONE = 'phone';

    /**
     * Who wrote an outgoing message, as the inbox shows it.
     */
    public function authorLabel(): string
    {
        return match (true) {
            $this->sent_from === self::SENT_FROM_PHONE => 'Celular',
            $this->sent_by_user_id !== null => 'Equipo',
            default => 'IA',
        };
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}

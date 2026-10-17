<?php

namespace App\Support;

use App\Models\Lead;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Limits how often a visitor can talk to the AI assistant (each message costs AI calls).
 */
class ChatThrottle
{
    public const MESSAGES_PER_MINUTE = 10;

    public const MESSAGES_PER_LEAD = 60;

    /**
     * Registers an attempt. Returns a friendly message when the visitor must wait, or null when allowed.
     */
    public static function attempt(string $ip, string $sessionId, ?Lead $lead = null): ?string
    {
        $key = 'chat:'.sha1($ip.'|'.$sessionId);

        if (RateLimiter::tooManyAttempts($key, self::MESSAGES_PER_MINUTE)) {
            return 'Estás escribiendo muy rápido 😅 Esperá unos segundos y seguimos.';
        }

        if ($lead && $lead->messages()->where('role', 'user')->count() >= self::MESSAGES_PER_LEAD) {
            return 'Llegamos al límite de esta conversación. Un asesor se va a comunicar con vos para continuar. 🙌';
        }

        RateLimiter::hit($key, 60);

        return null;
    }
}

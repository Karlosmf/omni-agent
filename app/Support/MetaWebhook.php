<?php

namespace App\Support;

use App\Enums\Channel;
use App\Services\Meta\MetaCredentials;
use Illuminate\Http\Request;

/**
 * Shared checks for Meta's webhooks (WhatsApp, Instagram, Messenger): handshake and payload signature.
 */
class MetaWebhook
{
    /**
     * Whether the hub.verify_token of the handshake matches any token the installation accepts.
     *
     * @param  list<Channel>  $channels
     */
    public static function isValidHandshake(Request $request, array $channels): bool
    {
        $given = (string) $request->query('hub_verify_token');

        if ($request->query('hub_mode') !== 'subscribe' || $given === '') {
            return false;
        }

        return collect(app(MetaCredentials::class)->verifyTokens($channels))->contains(fn (string $token) => hash_equals($token, $given));
    }

    /**
     * Whether X-Hub-Signature-256 matches the body, signed with any of the given secrets.
     *
     * @param  list<string|null>  $secrets
     */
    public static function hasValidSignature(Request $request, array $secrets): bool
    {
        $header = (string) $request->header('X-Hub-Signature-256');

        if (! str_starts_with($header, 'sha256=')) {
            return false;
        }

        foreach (array_filter($secrets) as $secret) {
            if (hash_equals('sha256='.hash_hmac('sha256', $request->getContent(), (string) $secret), $header)) {
                return true;
            }
        }

        return false;
    }
}

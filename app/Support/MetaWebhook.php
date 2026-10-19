<?php

namespace App\Support;

use App\Enums\Channel;
use App\Models\ChannelAccount;
use Illuminate\Http\Request;

/**
 * Shared checks for Meta's webhooks (WhatsApp, Instagram, Messenger): handshake and payload signature.
 */
class MetaWebhook
{
    /**
     * Whether the hub.verify_token of the handshake matches the .env token or a connected account's token.
     */
    public static function isValidHandshake(Request $request, ?string $envToken, array $channels): bool
    {
        if ($request->query('hub_mode') !== 'subscribe') {
            return false;
        }

        $given = (string) $request->query('hub_verify_token');

        if ($given === '') {
            return false;
        }

        if (filled($envToken) && hash_equals((string) $envToken, $given)) {
            return true;
        }

        return ChannelAccount::whereIn('channel', array_map(fn (Channel $c) => $c->value, $channels))
            ->whereNotNull('verify_token')
            ->where('is_active', true)
            ->pluck('verify_token')
            ->contains(fn (string $token) => hash_equals($token, $given));
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

    /**
     * Secrets that may have signed a request for the given channels: the app-wide one plus each connected account's.
     *
     * @param  list<Channel>  $channels
     * @return list<string>
     */
    public static function secretsFor(?string $envSecret, array $channels): array
    {
        $accounts = ChannelAccount::whereIn('channel', array_map(fn (Channel $c) => $c->value, $channels))
            ->where('is_active', true)->get()->pluck('app_secret')->all();

        return array_values(array_filter([$envSecret, ...$accounts]));
    }
}

<?php

namespace App\Services\Channels;

use App\Models\ChannelAccount;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Sends and looks up messages on Facebook Messenger and Instagram DMs through Meta's Graph API.
 */
class MetaSocialGateway
{
    /**
     * Send free-form text (only allowed within 24 h of the customer's last message).
     *
     * @return string The provider's message id.
     */
    public function sendText(ChannelAccount $account, string $recipientId, string $text): string
    {
        if (blank($account->access_token)) {
            throw new RuntimeException("La cuenta «{$account->label}» no tiene token de acceso.");
        }

        $response = Http::timeout(15)
            ->withToken($account->access_token)
            ->post($this->url("{$account->external_id}/messages"), [
                'recipient' => ['id' => $recipientId],
                'messaging_type' => 'RESPONSE',
                'message' => ['text' => $text],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Meta rechazó el mensaje: '.($response->json('error.message') ?? $response->status()));
        }

        return (string) $response->json('message_id');
    }

    /**
     * The customer's public name, or null when Meta does not return it.
     */
    public function profileName(ChannelAccount $account, string $userId): ?string
    {
        $response = Http::timeout(10)
            ->withToken((string) $account->access_token)
            ->get($this->url($userId), ['fields' => 'name']);

        return $response->successful() ? ($response->json('name') ?: $response->json('username')) : null;
    }

    private function url(string $path): string
    {
        return rtrim((string) config('services.whatsapp.graph_url'), '/').'/'.config('services.whatsapp.graph_version').'/'.ltrim($path, '/');
    }
}

<?php

namespace App\Services\Meta;

use App\Enums\Channel;
use App\Models\ChannelAccount;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Una llamada liviana a Meta para confirmar que un token sirve y apunta a la cuenta correcta.
 */
class MetaConnectionCheck
{
    public function __construct(private MetaCredentials $credentials) {}

    /**
     * La cuenta conectada en el panel.
     *
     * @return array{0: bool, 1: string}
     */
    public function account(ChannelAccount $account): array
    {
        if (blank($account->access_token)) {
            return [false, 'No tiene token de acceso.'];
        }

        return match (true) {
            $account->channel === Channel::WhatsApp => $this->get($this->credentials->graphUrl(), $account->external_id, $account->access_token, 'display_phone_number,verified_name'),
            $account->usesInstagramLogin() => $this->get($this->credentials->instagramGraphUrl(), 'me', $account->access_token, 'user_id,username'),
            default => $this->get($this->credentials->graphUrl(), $account->page_id ?: $account->external_id, $account->access_token, 'id,name'),
        };
    }

    /**
     * El número de WhatsApp cargado en el .env.
     *
     * @return array{0: bool, 1: string}
     */
    public function whatsappFromEnv(): array
    {
        return $this->get($this->credentials->graphUrl(), (string) $this->credentials->whatsappPhoneNumberId(), (string) $this->credentials->whatsappToken(), 'display_phone_number,verified_name');
    }

    /**
     * @return array{0: bool, 1: string}
     */
    private function get(string $host, string $node, string $token, string $fields): array
    {
        try {
            $response = Http::timeout(10)->withToken($token)->get("{$host}/{$this->credentials->graphVersion()}/{$node}", ['fields' => $fields]);
        } catch (ConnectionException $e) {
            return [false, 'No se pudo conectar con Meta: '.$e->getMessage()];
        }

        if ($response->failed()) {
            return [false, (string) ($response->json('error.message') ?? 'Meta respondió '.$response->status())];
        }

        return [true, (string) ($response->json('display_phone_number') ?? $response->json('username') ?? $response->json('name') ?? 'ok')];
    }
}

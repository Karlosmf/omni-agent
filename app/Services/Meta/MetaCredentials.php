<?php

namespace App\Services\Meta;

use App\Enums\Channel;
use App\Models\ChannelAccount;

/**
 * Único lugar donde se leen las credenciales de Meta.
 *
 * Hoy salen del .env (la instalación) y de las cuentas conectadas en el panel (cifradas en la base).
 * Para Embedded Signup alcanza con cambiar esta clase: nadie más sabe de dónde vienen.
 */
class MetaCredentials
{
    public function appId(): ?string
    {
        return config('services.meta.app_id') ?: null;
    }

    public function graphVersion(): string
    {
        return (string) config('services.meta.graph_version');
    }

    public function graphUrl(): string
    {
        return rtrim((string) config('services.whatsapp.graph_url'), '/');
    }

    public function instagramGraphUrl(): string
    {
        return rtrim((string) config('services.meta.instagram_graph_url'), '/');
    }

    /**
     * Secreto de la app que firma los webhooks de un canal (WhatsApp tiene el suyo en el .env).
     */
    public function appSecret(Channel $channel): ?string
    {
        return ($channel === Channel::WhatsApp ? config('services.whatsapp.app_secret') : config('services.meta.app_secret')) ?: null;
    }

    public function verifyToken(Channel $channel): ?string
    {
        return ($channel === Channel::WhatsApp ? config('services.whatsapp.verify_token') : config('services.meta.verify_token')) ?: null;
    }

    /**
     * El número de WhatsApp de la instalación (.env) cuando no hay una cuenta conectada.
     */
    public function whatsappPhoneNumberId(?ChannelAccount $account = null): ?string
    {
        return $account ? $account->external_id : (config('services.whatsapp.phone_number_id') ?: null);
    }

    public function whatsappToken(?ChannelAccount $account = null): ?string
    {
        return $account ? $account->access_token : (config('services.whatsapp.access_token') ?: null);
    }

    /**
     * Secretos que pueden haber firmado un webhook de estos canales: el de la instalación y los de cada cuenta activa.
     *
     * @param  list<Channel>  $channels
     * @return list<string>
     */
    public function webhookSecrets(array $channels): array
    {
        $fromAccounts = ChannelAccount::whereIn('channel', array_map(fn (Channel $c) => $c->value, $channels))
            ->where('is_active', true)->get()->pluck('app_secret')->all();

        $fromEnv = array_map(fn (Channel $c) => $this->appSecret($c), $channels);

        return array_values(array_unique(array_filter([...$fromEnv, ...$fromAccounts])));
    }

    /**
     * Tokens de verificación aceptados al registrar un webhook de estos canales.
     *
     * @param  list<Channel>  $channels
     * @return list<string>
     */
    public function verifyTokens(array $channels): array
    {
        $fromAccounts = ChannelAccount::whereIn('channel', array_map(fn (Channel $c) => $c->value, $channels))
            ->whereNotNull('verify_token')->where('is_active', true)->pluck('verify_token')->all();

        $fromEnv = array_map(fn (Channel $c) => $this->verifyToken($c), $channels);

        return array_values(array_unique(array_filter([...$fromEnv, ...$fromAccounts])));
    }
}

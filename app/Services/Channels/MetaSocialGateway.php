<?php

namespace App\Services\Channels;

use App\Enums\Channel;
use App\Enums\UserRole;
use App\Exceptions\MetaApiException;
use App\Models\ChannelAccount;
use App\Models\User;
use App\Services\Meta\MetaCredentials;
use App\Support\SocialEntryPoints;
use Filament\Notifications\Notification;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Sends and looks up messages on Facebook Messenger and Instagram DMs through Meta's Graph API.
 *
 * Instagram accounts connected with Instagram Login talk to graph.instagram.com with their own token;
 * everything else (Messenger, Instagram through a Facebook page) uses graph.facebook.com with the page token.
 */
class MetaSocialGateway
{
    public function __construct(private MetaCredentials $credentials = new MetaCredentials) {}

    /**
     * Send free-form text, split to the channel's byte limit and stripped of markdown.
     * Only allowed within 24 h of the customer's last message, or up to 7 days when a person
     * answers with Meta's HUMAN_AGENT tag.
     *
     * @return string The provider's id of the last message sent.
     *
     * @throws MetaApiException
     */
    public function sendText(ChannelAccount $account, string $recipientId, string $text, bool $humanAgent = false): string
    {
        if ($account->needsReconnect()) {
            throw new MetaApiException("La cuenta «{$account->label}» necesita reconectarse: el token venció o fue revocado.", 401);
        }

        if (blank($account->access_token)) {
            throw new MetaApiException("La cuenta «{$account->label}» no tiene token de acceso.");
        }

        $payload = [
            'recipient' => ['id' => $recipientId],
            'messaging_type' => $humanAgent ? 'MESSAGE_TAG' : 'RESPONSE',
        ] + ($humanAgent ? ['tag' => 'HUMAN_AGENT'] : []);

        $id = '';

        foreach (self::chunks(self::plainText($text), (int) $account->channel->maxTextBytes()) as $chunk) {
            $id = (string) $this->post($account, $this->sendUrl($account), $payload + ['message' => ['text' => $chunk]])->json('message_id');
        }

        return $id;
    }

    /**
     * Show the opening questions: ice breakers on Instagram; "Empezar" and a persistent menu on Messenger.
     * Customers tap them and Meta sends a postback (the app must be subscribed to messaging_postbacks).
     *
     * @throws MetaApiException
     */
    public function setEntryPoints(ChannelAccount $account): void
    {
        $questions = SocialEntryPoints::questions();
        $url = $this->host($account).'/'.$this->version().'/me/messenger_profile';

        if ($account->channel === Channel::Instagram) {
            $this->post($account, $account->usesInstagramLogin() ? $url : $url.'?platform=instagram', [
                'platform' => 'instagram',
                'ice_breakers' => [['call_to_actions' => $questions, 'locale' => 'default']],
            ]);

            return;
        }

        $this->post($account, $url, [
            'get_started' => ['payload' => SocialEntryPoints::GET_STARTED],
            'persistent_menu' => [[
                'locale' => 'default',
                'composer_input_disabled' => false,
                'call_to_actions' => array_map(fn (array $q) => ['type' => 'postback', 'title' => $q['question'], 'payload' => $q['payload']], $questions),
            ]],
        ]);
    }

    /**
     * The customer's public name, or null when Meta does not return it.
     */
    public function profileName(ChannelAccount $account, string $userId): ?string
    {
        $response = Http::timeout(10)
            ->withToken((string) $account->access_token)
            ->get($this->host($account).'/'.$this->version().'/'.$userId, ['fields' => 'name']);

        return $response->successful() ? ($response->json('name') ?: $response->json('username')) : null;
    }

    /**
     * Instagram and Messenger show plain text only: drop the markdown the AI likes to add.
     */
    public static function plainText(string $text): string
    {
        $text = preg_replace('/^#{1,6}\s+/m', '', $text);
        $text = preg_replace('/(\*\*|__)(.+?)\1/su', '$2', $text);
        $text = preg_replace('/(?<![\w*])\*(?!\s)(.+?)(?<!\s)\*(?![\w*])/su', '$1', $text);

        return trim(str_replace('`', '', $text));
    }

    /**
     * Split a text into pieces of at most $maxBytes bytes, breaking at spaces and line breaks.
     *
     * @return list<string>
     */
    public static function chunks(string $text, int $maxBytes): array
    {
        if ($maxBytes <= 0 || strlen($text) <= $maxBytes) {
            return [$text];
        }

        $chunks = [];
        $current = '';

        foreach (preg_split('/(\s+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE) as $piece) {
            while (strlen($piece) > $maxBytes) {
                $cut = mb_strcut($piece, 0, $maxBytes);
                $chunks[] = $cut;
                $piece = substr($piece, strlen($cut));
            }

            if (strlen($current.$piece) > $maxBytes) {
                $chunks[] = trim($current);
                $current = ltrim($piece);
            } else {
                $current .= $piece;
            }
        }

        if (trim($current) !== '') {
            $chunks[] = trim($current);
        }

        return array_values(array_filter($chunks, fn (string $c) => $c !== ''));
    }

    private function post(ChannelAccount $account, string $url, array $payload): Response
    {
        try {
            $response = Http::timeout(15)
                ->withToken($account->access_token)
                ->retry(2, 300, fn ($e) => $e instanceof ConnectionException || ($e instanceof RequestException && $e->response->serverError()), throw: false)
                ->post($url, $payload);
        } catch (ConnectionException $e) {
            throw new MetaApiException('No se pudo conectar con Meta: '.$e->getMessage());
        }

        if ($response->failed()) {
            $error = new MetaApiException(
                'Meta rechazó el mensaje: '.($response->json('error.message') ?? $response->status()),
                $response->status(),
                $response->json('error.code'),
            );

            if ($error->isAuthError()) {
                $this->markReconnectRequired($account, $error->getMessage());
            }

            throw $error;
        }

        if ($account->last_error !== null) {
            $account->forceFill(['last_error' => null])->saveQuietly();
        }

        return $response;
    }

    /**
     * The token is dead: stop sending, remember why and tell the admins once.
     */
    private function markReconnectRequired(ChannelAccount $account, string $reason): void
    {
        $wasConnected = ! $account->needsReconnect();
        $account->forceFill(['status' => 'reconnect_required', 'last_error' => Str::limit($reason, 500)])->saveQuietly();

        if (! $wasConnected) {
            return;
        }

        foreach (User::where('role', UserRole::Admin)->get() as $admin) {
            Notification::make()
                ->title('Reconectá «'.$account->label.'»')
                ->body('Meta rechazó el token de '.$account->channel->getLabel().'. Cargá uno nuevo en Canales de mensajería para volver a responder.')
                ->danger()
                ->sendToDatabase($admin);
        }
    }

    private function sendUrl(ChannelAccount $account): string
    {
        $id = $account->usesInstagramLogin() ? $account->external_id : ($account->page_id ?: $account->external_id);

        return $this->host($account).'/'.$this->version().'/'.$id.'/messages';
    }

    private function host(ChannelAccount $account): string
    {
        return $account->usesInstagramLogin() ? $this->credentials->instagramGraphUrl() : $this->credentials->graphUrl();
    }

    private function version(): string
    {
        return $this->credentials->graphVersion();
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\Channel;
use App\Jobs\ProcessIncomingSocialMessage;
use App\Models\Message;
use App\Services\Channels\InboundMessage;
use App\Services\Meta\MetaCredentials;
use App\Services\WhatsApp\WhatsAppConversationService;
use App\Support\MetaWebhook;
use App\Support\SocialEntryPoints;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Instagram DMs and Facebook Messenger share one Meta webhook (object = "instagram" | "page").
 */
class MetaSocialWebhookController extends Controller
{
    private const CHANNELS = [Channel::Instagram, Channel::Messenger];

    public function verify(Request $request, ?string $token = null): Response
    {
        if (! $this->hasValidPathToken($token)) {
            return response('Forbidden', 403);
        }

        if (MetaWebhook::isValidHandshake($request, self::CHANNELS)) {
            return response((string) $request->query('hub_challenge'), 200)->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }

    public function receive(Request $request, ?string $token = null): Response
    {
        if (! $this->hasValidPathToken($token)) {
            return response('Forbidden', 403);
        }

        if (! MetaWebhook::hasValidSignature($request, app(MetaCredentials::class)->webhookSecrets(self::CHANNELS))) {
            return response('Invalid signature', 403);
        }

        // Only Instagram ("instagram") and Messenger ("page") events belong here; anything else is acknowledged and ignored.
        $channel = match ($request->input('object')) {
            'instagram' => Channel::Instagram,
            'page' => Channel::Messenger,
            default => null,
        };

        if ($channel === null) {
            return response('OK', 200);
        }

        foreach ((array) $request->input('entry', []) as $entry) {
            foreach ((array) ($entry['messaging'] ?? []) as $event) {
                $this->handleEvent($channel, (string) ($entry['id'] ?? ''), (array) $event);
            }
        }

        return response('OK', 200);
    }

    /**
     * With META_WEBHOOK_TOKEN set, the URL itself is a second secret on top of the signature.
     */
    private function hasValidPathToken(?string $token): bool
    {
        $expected = config('services.meta.webhook_token');

        return blank($expected) || hash_equals((string) $expected, (string) $token);
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function handleEvent(Channel $channel, string $accountId, array $event): void
    {
        foreach ((array) ($event['delivery']['mids'] ?? []) as $mid) {
            Message::recordDeliveryStatus((string) $mid, 'delivered');
        }

        $message = $event['message'] ?? null;

        // "Seguir por Instagram/Messenger" from the web chat: the ref can arrive alone, before any message.
        $ref = $event['referral']['ref'] ?? $event['postback']['referral']['ref'] ?? null;

        if (! is_array($message) && is_string($ref) && $ref !== '') {
            app(WhatsAppConversationService::class)->linkSocialReferral($channel, $accountId, (string) ($event['sender']['id'] ?? ''), $ref);

            return;
        }

        // A tapped ice breaker / "Empezar" / menu item: it opens the conversation like a first message from the customer.
        if (! is_array($message) && is_string($payload = $event['postback']['payload'] ?? null)) {
            $this->handlePostback($channel, $accountId, (array) $event, $payload);

            return;
        }

        // Our own replies come back as echoes; read/delivery receipts and postbacks carry no customer text.
        if (! is_array($message) || ! empty($message['is_echo']) || empty($message['mid'])) {
            return;
        }

        $text = trim((string) ($message['text'] ?? ''));

        ProcessIncomingSocialMessage::dispatch(new InboundMessage(
            channel: $channel,
            accountExternalId: $accountId,
            senderId: (string) ($event['sender']['id'] ?? ''),
            externalId: (string) $message['mid'],
            text: $text !== '' ? $text : $this->placeholderFor($message),
            isText: $text !== '',
            referral: $event['referral'] ?? $message['referral'] ?? null,
        ));
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function handlePostback(Channel $channel, string $accountId, array $event, string $payload): void
    {
        $text = SocialEntryPoints::textFor($payload);
        $senderId = (string) ($event['sender']['id'] ?? '');

        if ($text === null || $senderId === '') {
            return;
        }

        $externalId = (string) ($event['postback']['mid'] ?? 'pb:'.sha1($senderId.($event['timestamp'] ?? '').$payload));

        ProcessIncomingSocialMessage::dispatch(new InboundMessage(
            channel: $channel,
            accountExternalId: $accountId,
            senderId: $senderId,
            externalId: $externalId,
            text: $text,
            referral: $event['postback']['referral'] ?? null,
        ));
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function placeholderFor(array $message): string
    {
        return match ($message['attachments'][0]['type'] ?? null) {
            'image' => '[Imagen]',
            'audio' => '[Audio]',
            'video', 'ig_reel' => '[Video]',
            'file' => '[Documento]',
            'share', 'story_mention' => '[Publicación compartida]',
            'location' => '[Ubicación]',
            default => '[Mensaje no compatible]',
        };
    }
}

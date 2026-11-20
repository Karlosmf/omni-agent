<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsAppTemplate;
use App\Models\ZernioConversation;
use App\Support\ListMessage;
use App\Support\PhoneNumber;
use App\Support\ReplyButtons;
use Carbon\CarbonInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * WhatsApp through Zernio, a Meta Tech Provider. It is what lets the agency's number stay in the WhatsApp
 * Business app on the phone (coexistence) while the inbox and the AI answer through the API.
 */
class ZernioGateway implements WhatsAppGateway
{
    /** Attempts when the connection to Zernio drops (an answer from Zernio, even an error, is never retried). */
    private const CONNECTION_ATTEMPTS = 3;

    private const RETRY_DELAY_MS = 300;

    public function isConfigured(): bool
    {
        return filled(config('services.zernio.api_key')) && filled(config('services.zernio.whatsapp_account_id'));
    }

    public function sendText(string $to, string $text, array $buttons = []): string
    {
        $payload = ['message' => $text];

        if ($buttons !== []) {
            $payload['buttons'] = array_map(
                fn (string $title, int $position) => ['type' => 'postback', 'title' => $title, 'payload' => ReplyButtons::payloadFor($title, $position)],
                $buttons,
                array_keys($buttons),
            );
        }

        return $this->sendInConversation($to, $payload);
    }

    public function sendList(string $to, string $body, string $button, array $rows, ?string $header = null, ?string $sectionTitle = null): string
    {
        return $this->sendInConversation($to, [
            'interactive' => ListMessage::interactive($body, $button, $rows, $header, $sectionTitle),
        ]);
    }

    public function sendDocument(string $to, string $url, string $filename, ?string $caption = null): string
    {
        return $this->sendInConversation($to, array_filter([
            'message' => $caption,
            'attachmentUrl' => $url,
            'attachmentType' => 'file',
            'attachmentName' => $filename,
        ], fn ($value) => $value !== null && $value !== ''));
    }

    /**
     * Zernio answers inside an existing conversation: the customer must have written first (always true inside the 24 h window).
     *
     * @param  array<string, mixed>  $payload
     */
    private function sendInConversation(string $to, array $payload): string
    {
        $conversationId = ZernioConversation::idFor($to);

        if ($conversationId === null) {
            throw new RuntimeException('Zernio todavía no tiene una conversación con este número: enviá una plantilla.');
        }

        return (string) $this->post("inbox/conversations/{$conversationId}/messages", [
            'accountId' => config('services.zernio.whatsapp_account_id'),
        ] + $payload)->json('data.messageId');
    }

    public function sendTemplate(string $to, WhatsAppTemplate $template, array $variables = []): string
    {
        $response = $this->post('inbox/conversations', [
            'accountId' => config('services.zernio.whatsapp_account_id'),
            'participantId' => ltrim($to, '+'),
            'templateName' => $template->name,
            'templateLanguage' => $template->language,
            'templateParams' => array_values($variables),
        ]);

        ZernioConversation::remember($to, $response->json('data.conversationId') ?? $response->json('data.id'));

        return (string) $response->json('data.messageId');
    }

    /**
     * The Zernio conversation with a number: the remembered one, or found among the account's conversations.
     */
    public function conversationIdFor(string $phone, int $maxPages = 10): ?string
    {
        if ($known = ZernioConversation::idFor($phone)) {
            return $known;
        }

        $participant = ltrim((string) PhoneNumber::toE164('+'.ltrim($phone, '+')), '+');
        $cursor = null;

        for ($page = 0; $page < $maxPages; $page++) {
            $response = $this->get('inbox/conversations', array_filter([
                'accountId' => config('services.zernio.whatsapp_account_id'),
                'limit' => 100,
                'cursor' => $cursor,
            ]));

            foreach ((array) $response->json('data', []) as $conversation) {
                if (ltrim((string) ($conversation['participantId'] ?? ''), '+') === $participant) {
                    ZernioConversation::remember($participant, $conversation['id'] ?? null);

                    return $conversation['id'] ?? null;
                }
            }

            $cursor = $response->json('pagination.nextCursor');

            if (! $response->json('pagination.hasMore') || blank($cursor)) {
                return null;
            }
        }

        return null;
    }

    /**
     * Messages of a conversation since a date, oldest first, as Zernio lists them.
     *
     * @return list<array<string, mixed>>
     */
    public function messagesSince(string $conversationId, CarbonInterface $since, int $maxPages = 10): array
    {
        $messages = [];
        $cursor = null;

        for ($page = 0; $page < $maxPages; $page++) {
            $response = $this->get("inbox/conversations/{$conversationId}/messages", array_filter([
                'accountId' => config('services.zernio.whatsapp_account_id'),
                'limit' => 100,
                'sortOrder' => 'desc',
                'cursor' => $cursor,
            ]));

            foreach ((array) $response->json('messages', []) as $message) {
                if (Carbon::parse($message['createdAt'] ?? '2000-01-01')->lt($since)) {
                    return array_reverse($messages);
                }

                $messages[] = $message;
            }

            $cursor = $response->json('pagination.nextCursor');

            if (blank($cursor) || $response->json('pagination.hasMore') === false) {
                break;
            }
        }

        return array_reverse($messages);
    }

    /**
     * A send is retried when the connection drops. The same idempotency key goes with every attempt, so a message
     * that reached Zernio before the connection dropped is not sent to the customer twice.
     *
     * @param  array<string, mixed>  $payload
     */
    private function post(string $path, array $payload): Response
    {
        $key = (string) Str::uuid();

        return $this->send(
            fn (PendingRequest $http, string $url) => $http->withHeaders(['Idempotency-Key' => $key])->post($url, $payload),
            $path,
            'Zernio rechazó el mensaje',
        );
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function get(string $path, array $query): Response
    {
        return $this->send(fn (PendingRequest $http, string $url) => $http->get($url, $query), $path, 'Zernio no devolvió los mensajes');
    }

    /**
     * @param  callable(PendingRequest, string): Response  $call
     */
    private function send(callable $call, string $path, string $failure): Response
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Zernio no está configurado: completá ZERNIO_API_KEY y ZERNIO_WHATSAPP_ACCOUNT_ID en el .env.');
        }

        try {
            $response = $call(
                Http::timeout(15)
                    ->withToken((string) config('services.zernio.api_key'))
                    ->retry(self::CONNECTION_ATTEMPTS, self::RETRY_DELAY_MS, fn (Throwable $e) => $e instanceof ConnectionException, throw: false),
                rtrim((string) config('services.zernio.base_url'), '/').'/'.$path,
            );
        } catch (ConnectionException $e) {
            throw new RuntimeException('No se pudo conectar con Zernio: '.$e->getMessage());
        }

        if ($response->failed()) {
            throw new RuntimeException($failure.': '.($response->json('error') ?? $response->status()));
        }

        return $response;
    }
}

<?php

namespace App\Services\WhatsApp;

use App\Jobs\ProcessIncomingWhatsApp;
use App\Jobs\ProcessWhatsAppBusinessAppEcho;
use App\Models\Message;
use App\Models\ZernioConversation;
use App\Support\PhoneNumber;
use Illuminate\Support\Carbon;

/**
 * Turns a Zernio inbox event into the Cloud API shape so the usual WhatsApp jobs handle it.
 * Events come from the signed webhook or, when testing locally, from polling Zernio.
 */
class ZernioEventHandler
{
    private const STATUSES = ['message.delivered' => 'delivered', 'message.read' => 'read', 'message.failed' => 'failed'];

    /**
     * @param  array<string, mixed>  $event
     */
    public function handle(array $event): void
    {
        if ((string) ($event['account']['accountId'] ?? $event['account']['id'] ?? '') !== (string) config('services.zernio.whatsapp_account_id')) {
            return;
        }

        $message = (array) ($event['message'] ?? []);
        $externalId = (string) ($message['platformMessageId'] ?? $message['id'] ?? '');
        $name = (string) ($event['event'] ?? '');

        if ($name === 'message.received' && ($message['direction'] ?? 'incoming') === 'incoming') {
            $phone = (string) ($message['sender']['phoneNumber'] ?? '');

            if (! $this->isAllowed($phone)) {
                return;
            }

            ZernioConversation::remember($phone, $message['conversationId'] ?? null);
            ProcessIncomingWhatsApp::dispatch($this->toCloudMessage($externalId, $phone, $message), $message['sender']['name'] ?? null, null, $event);
        } elseif ($name === 'message.sent' && ($message['source'] ?? null) === 'whatsapp_business_app') {
            $phone = (string) ($event['conversation']['participantId'] ?? $message['conversation']['participantId'] ?? '');

            if (! $this->isAllowed($phone)) {
                return;
            }

            ZernioConversation::remember($phone, $message['conversationId'] ?? null);
            $sentAt = $message['sentAt'] ?? $message['createdAt'] ?? $event['timestamp'] ?? null;

            ProcessWhatsAppBusinessAppEcho::dispatch(['to' => $phone] + ($sentAt ? ['timestamp' => Carbon::parse($sentAt)->timestamp] : []) + $this->toCloudMessage($externalId, $phone, $message), null, $event);
        } elseif (isset(self::STATUSES[$name]) && $externalId !== '') {
            Message::where('external_id', $externalId)->update(['status' => self::STATUSES[$name]]);
        }
    }

    /**
     * In test mode (ZERNIO_TEST_PHONES) only the listed numbers reach the inbox and the AI.
     */
    public function isAllowed(string $phone): bool
    {
        $allowed = $this->testPhones();

        if ($allowed === []) {
            return true;
        }

        return in_array(PhoneNumber::toE164('+'.ltrim($phone, '+')), $allowed, true);
    }

    /**
     * @return list<string> E.164 numbers, empty when test mode is off.
     */
    public function testPhones(): array
    {
        return collect(explode(',', (string) config('services.zernio.test_phones')))
            ->map(fn (string $number) => PhoneNumber::toE164('+'.ltrim(trim($number), '+')))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * A Zernio message in the Cloud API shape the conversation service reads (text, button tap or attachment type).
     *
     * @param  array<string, mixed>  $message  With `text`, `attachments` and `metadata` as Zernio sends them.
     * @return array<string, mixed>
     */
    public function toCloudMessage(string $id, string $phone, array $message): array
    {
        $from = ltrim($phone, '+');

        if ($tap = $this->buttonTap($message)) {
            return ['id' => $id, 'from' => $from, 'type' => 'interactive', 'interactive' => ['type' => 'button_reply', 'button_reply' => $tap]];
        }

        $type = match ($message['attachments'][0]['type'] ?? null) {
            null => 'text',
            'image', 'audio', 'video', 'document', 'sticker', 'location' => $message['attachments'][0]['type'],
            'file' => 'document',
            default => 'unsupported',
        };

        return ['id' => $id, 'from' => $from, 'type' => $type, 'text' => ['body' => (string) ($message['text'] ?? '')]];
    }

    /**
     * A tapped reply button or list row. Zernio documents the tap as `metadata.interactiveType` and `metadata.interactiveId`;
     * it names no key for the title, so the message text (the title WhatsApp shows) is the answer the conversation keeps.
     * A tap that arrives without text is still a tap: its id says which option it was (a chat menu row resolves its title).
     *
     * @param  array<string, mixed>  $message
     * @return array{id: string, title: string}|null
     */
    private function buttonTap(array $message): ?array
    {
        $metadata = (array) ($message['metadata'] ?? []);
        $type = $metadata['interactiveType'] ?? null;
        $payload = $metadata['interactiveId'] ?? $metadata['buttonPayload'] ?? $metadata['payload'] ?? null;

        // Flow answers and orders also arrive as interactive messages, but they are not button taps.
        if (in_array($type, ['nfm_reply', 'flow', 'order'], true) || ($type === null && ! is_string($payload))) {
            return null;
        }

        $id = trim((string) ($payload ?? ''));
        $title = trim((string) ($metadata['buttonTitle'] ?? $metadata['title'] ?? $message['text'] ?? ''));

        return $title === '' && $id === '' ? null : ['id' => $id, 'title' => $title];
    }
}

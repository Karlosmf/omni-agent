<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessIncomingWhatsApp;
use App\Jobs\ProcessWhatsAppBusinessAppEcho;
use App\Models\Message;
use App\Models\ZernioConversation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * WhatsApp events relayed by Zernio. They are translated to the Cloud API shape so the same jobs handle them.
 */
class ZernioWebhookController extends Controller
{
    private const STATUSES = ['message.delivered' => 'delivered', 'message.read' => 'read', 'message.failed' => 'failed'];

    public function receive(Request $request): Response
    {
        $secret = (string) config('services.zernio.webhook_secret');
        $signature = (string) $request->header('X-Zernio-Signature');

        if ($secret === '' || $signature === '') {
            return response('Missing signature', 401);
        }

        if (! hash_equals(hash_hmac('sha256', $request->getContent(), $secret), $signature)) {
            return response('Invalid signature', 400);
        }

        if ((string) $request->input('account.accountId', $request->input('account.id')) !== (string) config('services.zernio.whatsapp_account_id')) {
            return response('OK', 200);
        }

        $message = (array) $request->input('message', []);
        $externalId = (string) ($message['platformMessageId'] ?? $message['id'] ?? '');
        $event = (string) $request->input('event');

        if ($event === 'message.received' && ($message['direction'] ?? 'incoming') === 'incoming') {
            $phone = (string) ($message['sender']['phoneNumber'] ?? '');
            ZernioConversation::remember($phone, $message['conversationId'] ?? null);

            ProcessIncomingWhatsApp::dispatch($this->toCloudMessage($externalId, $phone, $message), $message['sender']['name'] ?? null);
        } elseif ($event === 'message.sent' && ($message['source'] ?? null) === 'whatsapp_business_app') {
            $phone = (string) $request->input('conversation.participantId', $message['conversation']['participantId'] ?? '');
            ZernioConversation::remember($phone, $message['conversationId'] ?? null);

            ProcessWhatsAppBusinessAppEcho::dispatch(['to' => $phone] + $this->toCloudMessage($externalId, $phone, $message));
        } elseif (isset(self::STATUSES[$event]) && $externalId !== '') {
            Message::where('external_id', $externalId)->update(['status' => self::STATUSES[$event]]);
        }

        return response('OK', 200);
    }

    /**
     * @param  array<string, mixed>  $message
     * @return array<string, mixed>
     */
    private function toCloudMessage(string $id, string $phone, array $message): array
    {
        $type = match ($message['attachments'][0]['type'] ?? null) {
            null => 'text',
            'image', 'audio', 'video', 'document', 'sticker', 'location' => $message['attachments'][0]['type'],
            'file' => 'document',
            default => 'unsupported',
        };

        return ['id' => $id, 'from' => ltrim($phone, '+'), 'type' => $type, 'text' => ['body' => (string) ($message['text'] ?? '')]];
    }
}

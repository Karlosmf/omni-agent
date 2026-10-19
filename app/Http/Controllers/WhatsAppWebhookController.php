<?php

namespace App\Http\Controllers;

use App\Enums\Channel;
use App\Jobs\ProcessIncomingWhatsApp;
use App\Models\Message;
use App\Support\MetaWebhook;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WhatsAppWebhookController extends Controller
{
    /**
     * Meta's one-time verification handshake when the webhook is registered.
     */
    public function verify(Request $request): Response
    {
        if (MetaWebhook::isValidHandshake($request, config('services.whatsapp.verify_token'), [Channel::WhatsApp])) {
            return response((string) $request->query('hub_challenge'), 200)->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }

    /**
     * Receive messages and delivery statuses. Answers 200 fast; the work happens on the queue.
     */
    public function receive(Request $request): Response
    {
        if (! $this->hasValidSignature($request)) {
            return response('Invalid signature', 403);
        }

        foreach ((array) $request->input('entry', []) as $entry) {
            foreach ((array) ($entry['changes'] ?? []) as $change) {
                $value = $change['value'] ?? [];
                $names = collect($value['contacts'] ?? [])->mapWithKeys(fn ($c) => [$c['wa_id'] ?? '' => $c['profile']['name'] ?? null]);

                foreach ((array) ($value['messages'] ?? []) as $message) {
                    ProcessIncomingWhatsApp::dispatch($message, $names[$message['from'] ?? ''] ?? null, $value['metadata']['phone_number_id'] ?? null);
                }

                foreach ((array) ($value['statuses'] ?? []) as $status) {
                    Message::where('external_id', $status['id'] ?? '')->update(['status' => $status['status'] ?? 'sent']);
                }
            }
        }

        return response('OK', 200);
    }

    private function hasValidSignature(Request $request): bool
    {
        return MetaWebhook::hasValidSignature($request, MetaWebhook::secretsFor(config('services.whatsapp.app_secret'), [Channel::WhatsApp]));
    }
}

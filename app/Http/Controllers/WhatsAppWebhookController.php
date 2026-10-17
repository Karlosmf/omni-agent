<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessIncomingWhatsApp;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WhatsAppWebhookController extends Controller
{
    /**
     * Meta's one-time verification handshake when the webhook is registered.
     */
    public function verify(Request $request): Response
    {
        $expected = (string) config('services.whatsapp.verify_token');

        if ($expected !== '' && $request->query('hub_mode') === 'subscribe' && hash_equals($expected, (string) $request->query('hub_verify_token'))) {
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
                    ProcessIncomingWhatsApp::dispatch($message, $names[$message['from'] ?? ''] ?? null);
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
        $secret = (string) config('services.whatsapp.app_secret');
        $header = (string) $request->header('X-Hub-Signature-256');

        if ($secret === '' || ! str_starts_with($header, 'sha256=')) {
            return false;
        }

        return hash_equals('sha256='.hash_hmac('sha256', $request->getContent(), $secret), $header);
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\WhatsApp\ZernioEventHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * WhatsApp events relayed by Zernio, signed with the secret registered with the webhook.
 */
class ZernioWebhookController extends Controller
{
    public function receive(Request $request, ZernioEventHandler $events): Response
    {
        $secret = (string) config('services.zernio.webhook_secret');
        // Hex HMAC-SHA256 of the raw body, accepted with or without the "sha256=" prefix some senders add.
        $signature = strtolower(trim((string) $request->header('X-Zernio-Signature')));
        $signature = str_starts_with($signature, 'sha256=') ? substr($signature, 7) : $signature;

        if ($secret === '' || $signature === '') {
            return response('Missing signature', 401);
        }

        if (! hash_equals(hash_hmac('sha256', $request->getContent(), $secret), $signature)) {
            return response('Invalid signature', 400);
        }

        $events->handle($request->all());

        return response('OK', 200);
    }
}

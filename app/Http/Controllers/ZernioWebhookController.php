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
        $signature = (string) $request->header('X-Zernio-Signature');

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

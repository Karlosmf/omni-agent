<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsAppTemplate;
use App\Models\ZernioConversation;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * WhatsApp through Zernio, a Meta Tech Provider. It is what lets the agency's number stay in the WhatsApp
 * Business app on the phone (coexistence) while the inbox and the AI answer through the API.
 */
class ZernioGateway implements WhatsAppGateway
{
    public function isConfigured(): bool
    {
        return filled(config('services.zernio.api_key')) && filled(config('services.zernio.whatsapp_account_id'));
    }

    /**
     * Zernio answers inside an existing conversation: the customer must have written first (always true inside the 24 h window).
     */
    public function sendText(string $to, string $text): string
    {
        $conversationId = ZernioConversation::idFor($to);

        if ($conversationId === null) {
            throw new RuntimeException('Zernio todavía no tiene una conversación con este número: enviá una plantilla.');
        }

        return (string) $this->post("inbox/conversations/{$conversationId}/messages", [
            'accountId' => config('services.zernio.whatsapp_account_id'),
            'message' => $text,
        ])->json('data.messageId');
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
     * @param  array<string, mixed>  $payload
     */
    private function post(string $path, array $payload): Response
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Zernio no está configurado: completá ZERNIO_API_KEY y ZERNIO_WHATSAPP_ACCOUNT_ID en el .env.');
        }

        try {
            $response = Http::timeout(15)
                ->withToken((string) config('services.zernio.api_key'))
                ->post(rtrim((string) config('services.zernio.base_url'), '/').'/'.$path, $payload);
        } catch (ConnectionException $e) {
            throw new RuntimeException('No se pudo conectar con Zernio: '.$e->getMessage());
        }

        if ($response->failed()) {
            throw new RuntimeException('Zernio rechazó el mensaje: '.($response->json('error') ?? $response->status()));
        }

        return $response;
    }
}

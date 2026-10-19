<?php

namespace App\Services\WhatsApp;

use App\Models\ChannelAccount;
use App\Models\WhatsAppTemplate;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class CloudApiGateway implements WhatsAppGateway
{
    private ?ChannelAccount $account = null;

    /**
     * A copy of this gateway that sends from a specific connected number instead of the .env default.
     */
    public function forAccount(?ChannelAccount $account): static
    {
        $clone = clone $this;
        $clone->account = $account;

        return $clone;
    }

    public function isConfigured(): bool
    {
        return filled($this->phoneNumberId()) && filled($this->accessToken());
    }

    private function phoneNumberId(): ?string
    {
        return $this->account ? $this->account->external_id : config('services.whatsapp.phone_number_id');
    }

    private function accessToken(): ?string
    {
        return $this->account ? $this->account->access_token : config('services.whatsapp.access_token');
    }

    public function sendText(string $to, string $text): string
    {
        return $this->send([
            'messaging_product' => 'whatsapp',
            'to' => ltrim($to, '+'),
            'type' => 'text',
            'text' => ['preview_url' => false, 'body' => $text],
        ]);
    }

    public function sendTemplate(string $to, WhatsAppTemplate $template, array $variables = []): string
    {
        return $this->send([
            'messaging_product' => 'whatsapp',
            'to' => ltrim($to, '+'),
            'type' => 'template',
            'template' => [
                'name' => $template->name,
                'language' => ['code' => $template->language],
                'components' => $variables === [] ? [] : [[
                    'type' => 'body',
                    'parameters' => array_map(fn (string $value) => ['type' => 'text', 'text' => $value], array_values($variables)),
                ]],
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function send(array $payload): string
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('WhatsApp no está configurado: completá las variables WHATSAPP_* en el .env.');
        }

        $version = config('services.whatsapp.graph_version');
        $response = Http::timeout(15)
            ->withToken($this->accessToken())
            ->post(rtrim((string) config('services.whatsapp.graph_url'), '/')."/{$version}/".$this->phoneNumberId().'/messages', $payload);

        if ($response->failed()) {
            throw new RuntimeException('WhatsApp rechazó el mensaje: '.($response->json('error.message') ?? $response->status()));
        }

        return (string) $response->json('messages.0.id');
    }
}

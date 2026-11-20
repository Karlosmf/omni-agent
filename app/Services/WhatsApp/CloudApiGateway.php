<?php

namespace App\Services\WhatsApp;

use App\Models\ChannelAccount;
use App\Models\WhatsAppTemplate;
use App\Services\Meta\MetaCredentials;
use App\Support\ListMessage;
use App\Support\ReplyButtons;
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
        return app(MetaCredentials::class)->whatsappPhoneNumberId($this->account);
    }

    private function accessToken(): ?string
    {
        return app(MetaCredentials::class)->whatsappToken($this->account);
    }

    public function sendText(string $to, string $text, array $buttons = []): string
    {
        if ($buttons === []) {
            return $this->send([
                'messaging_product' => 'whatsapp',
                'to' => ltrim($to, '+'),
                'type' => 'text',
                'text' => ['preview_url' => false, 'body' => $text],
            ]);
        }

        return $this->send([
            'messaging_product' => 'whatsapp',
            'to' => ltrim($to, '+'),
            'type' => 'interactive',
            'interactive' => [
                'type' => 'button',
                'body' => ['text' => $text],
                'action' => ['buttons' => array_map(
                    fn (string $title, int $position) => ['type' => 'reply', 'reply' => ['id' => ReplyButtons::payloadFor($title, $position), 'title' => $title]],
                    $buttons,
                    array_keys($buttons),
                )],
            ],
        ]);
    }

    public function sendList(string $to, string $body, string $button, array $rows, ?string $header = null, ?string $sectionTitle = null): string
    {
        return $this->send([
            'messaging_product' => 'whatsapp',
            'to' => ltrim($to, '+'),
            'type' => 'interactive',
            'interactive' => ListMessage::interactive($body, $button, $rows, $header, $sectionTitle),
        ]);
    }

    public function sendDocument(string $to, string $url, string $filename, ?string $caption = null): string
    {
        return $this->send([
            'messaging_product' => 'whatsapp',
            'to' => ltrim($to, '+'),
            'type' => 'document',
            'document' => array_filter(['link' => $url, 'filename' => $filename, 'caption' => $caption], fn ($value) => $value !== null && $value !== ''),
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

        $meta = app(MetaCredentials::class);
        $response = Http::timeout(15)
            ->withToken($this->accessToken())
            ->post($meta->graphUrl().'/'.$meta->graphVersion().'/'.$this->phoneNumberId().'/messages', $payload);

        if ($response->failed()) {
            throw new RuntimeException('WhatsApp rechazó el mensaje: '.($response->json('error.message') ?? $response->status()));
        }

        return (string) $response->json('messages.0.id');
    }
}

<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsAppTemplate;

/**
 * Provider-agnostic WhatsApp sender. The default implementation is Meta's official Cloud API.
 */
interface WhatsAppGateway
{
    public function isConfigured(): bool;

    /**
     * Send free-form text (only allowed within 24 h of the customer's last message).
     *
     * @param  list<string>  $buttons  Up to three reply buttons under the text; a tap comes back as a message with the button's title.
     * @return string The provider's message id.
     */
    public function sendText(string $to, string $text, array $buttons = []): string;

    /**
     * Send a document from a public URL, with an optional caption (same 24 h rule as text).
     *
     * @return string The provider's message id.
     */
    public function sendDocument(string $to, string $url, string $filename, ?string $caption = null): string;

    /**
     * Send a list message: a text with a button that opens up to ten options (same 24 h rule as text).
     * A tap comes back as a message carrying the row's id and title.
     *
     * @param  list<array{id: string, title: string, description?: string}>  $rows  Titles up to 24 characters, descriptions up to 72.
     * @return string The provider's message id.
     */
    public function sendList(string $to, string $body, string $button, array $rows, ?string $header = null, ?string $sectionTitle = null): string;

    /**
     * Send a template approved by Meta (allowed at any time).
     *
     * @param  list<string>  $variables
     * @return string The provider's message id.
     */
    public function sendTemplate(string $to, WhatsAppTemplate $template, array $variables = []): string;
}

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
     * @return string The provider's message id.
     */
    public function sendText(string $to, string $text): string;

    /**
     * Send a template approved by Meta (allowed at any time).
     *
     * @param  list<string>  $variables
     * @return string The provider's message id.
     */
    public function sendTemplate(string $to, WhatsAppTemplate $template, array $variables = []): string;
}

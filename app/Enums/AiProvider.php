<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum AiProvider: string implements HasColor, HasIcon, HasLabel
{
    case None = 'none';
    case Gemini = 'gemini';
    case OpenAI = 'openai';
    case Groq = 'groq';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::None => 'Sin IA (Desactivado)',
            self::Gemini => 'Google Gemini',
            self::OpenAI => 'OpenAI (ChatGPT)',
            self::Groq => 'Groq (gratis)',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::None => 'gray',
            self::Gemini => 'info',
            self::OpenAI => 'success',
            self::Groq => 'warning',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::None => 'heroicon-o-no-symbol',
            self::Gemini => 'heroicon-o-sparkles',
            self::OpenAI => 'heroicon-o-cpu-chip',
            self::Groq => 'heroicon-o-bolt',
        };
    }

    public function getDefaultModel(): string
    {
        return match ($this) {
            self::None => '',
            self::Gemini => 'gemini-2.0-flash',
            self::OpenAI => 'gpt-4o-mini',
            self::Groq => 'llama-3.3-70b-versatile',
        };
    }

    public function getApiKeyPlaceholder(): string
    {
        return match ($this) {
            self::None => 'No requiere API Key',
            self::Gemini => 'AIzaSy...',
            self::OpenAI => 'sk-proj-...',
            self::Groq => 'gsk_...',
        };
    }

    public function getHelperText(): string
    {
        return match ($this) {
            self::None => 'La IA estará desactivada en toda la plataforma.',
            self::Gemini => 'Obtené tu key en https://aistudio.google.com/apikey',
            self::OpenAI => 'Obtené tu key en https://platform.openai.com/api-keys',
            self::Groq => 'Plan gratuito con límite de uso. Obtené tu key en https://console.groq.com/keys. No lee PDFs.',
        };
    }

    /**
     * Whether the provider can read a PDF sent directly (used by the travel idea importer).
     */
    public function supportsPdf(): bool
    {
        return in_array($this, [self::Gemini, self::OpenAI], true);
    }

    /**
     * Chat completions endpoint for the providers that speak the OpenAI protocol.
     */
    public function getChatCompletionsUrl(): ?string
    {
        return match ($this) {
            self::OpenAI => 'https://api.openai.com/v1/chat/completions',
            self::Groq => 'https://api.groq.com/openai/v1/chat/completions',
            default => null,
        };
    }
}

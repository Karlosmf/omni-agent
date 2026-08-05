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

    public function getLabel(): ?string
    {
        return match ($this) {
            self::None => 'Sin IA (Desactivado)',
            self::Gemini => 'Google Gemini',
            self::OpenAI => 'OpenAI (ChatGPT)',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::None => 'gray',
            self::Gemini => 'info',
            self::OpenAI => 'success',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::None => 'heroicon-o-no-symbol',
            self::Gemini => 'heroicon-o-sparkles',
            self::OpenAI => 'heroicon-o-cpu-chip',
        };
    }

    public function getDefaultModel(): string
    {
        return match ($this) {
            self::None => '',
            self::Gemini => 'gemini-2.0-flash',
            self::OpenAI => 'gpt-4o-mini',
        };
    }

    public function getApiKeyPlaceholder(): string
    {
        return match ($this) {
            self::None => 'No requiere API Key',
            self::Gemini => 'AIzaSy...',
            self::OpenAI => 'sk-proj-...',
        };
    }

    public function getHelperText(): string
    {
        return match ($this) {
            self::None => 'La IA estará desactivada en toda la plataforma.',
            self::Gemini => 'Obtené tu key en https://aistudio.google.com/apikey',
            self::OpenAI => 'Obtené tu key en https://platform.openai.com/api-keys',
        };
    }
}

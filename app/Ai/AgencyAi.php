<?php

namespace App\Ai;

use App\Enums\AiProvider;
use Exception;
use Laravel\Ai\Ai;

/**
 * Turns the agency's AI settings (provider, key, model) into what the Laravel AI SDK needs to run an agent.
 * Each agency brings its own key, so the provider is built on demand instead of living in config/ai.php.
 */
class AgencyAi
{
    /**
     * Whether an AI provider is switched on (the key may still be missing).
     */
    public function isEnabled(): bool
    {
        return (get_agency_settings()?->ai_provider ?? AiProvider::None) !== AiProvider::None;
    }

    public function provider(): AiProvider
    {
        return get_agency_settings()?->ai_provider ?? AiProvider::None;
    }

    /**
     * The SDK provider name and model to prompt with.
     *
     * @return array{0: string, 1: string}
     *
     * @throws Exception AI_DISABLED or AI_API_KEY_MISSING.
     */
    public function resolve(): array
    {
        $settings = get_agency_settings();
        $provider = $settings?->ai_provider ?? AiProvider::None;

        if ($provider === AiProvider::None) {
            throw new Exception('AI_DISABLED');
        }

        // The legacy gemini_api_key keeps working for agencies that never re-saved their settings.
        $key = filled($settings?->ai_api_key) ? $settings->ai_api_key : $settings?->gemini_api_key;

        if (blank($key)) {
            throw new Exception('AI_API_KEY_MISSING');
        }

        $driver = $this->driverFor($provider);
        $url = $provider === AiProvider::OpenAiCompatible ? trim((string) $settings->ai_base_url) : '';
        $model = filled($settings?->ai_model) ? trim($settings->ai_model) : $provider->getDefaultModel();

        if ($provider === AiProvider::OpenAiCompatible && ($url === '' || $model === '')) {
            throw new Exception('Falta completar la URL y el modelo del proveedor compatible con OpenAI.');
        }

        $name = 'agency_'.substr(sha1($driver.'|'.$key.'|'.$url), 0, 16);

        Ai::build(['name' => $name, 'driver' => $driver, 'key' => $key] + ($url !== '' ? ['url' => $url] : []));

        return [$name, $model];
    }

    private function driverFor(AiProvider $provider): string
    {
        return match ($provider) {
            AiProvider::Gemini => 'gemini',
            AiProvider::OpenAI => 'openai',
            AiProvider::Groq => 'groq',
            AiProvider::OpenAiCompatible => 'openai-compatible',
            AiProvider::None => throw new Exception('AI_DISABLED'),
        };
    }
}

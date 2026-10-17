<?php

namespace App\Services;

use App\Enums\AiProvider;
use App\Enums\LeadTemperature;
use App\Models\Lead;
use Gemini\Data\GenerationConfig;
use Gemini\Enums\ResponseMimeType;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class AiConciergeService
{
    /**
     * Maximum retries for AI API calls.
     */
    private const MAX_RETRIES = 2;

    /**
     * Messages to include in context.
     */
    private const CONTEXT_MESSAGE_COUNT = 15;

    /**
     * Output budget for long structured answers (a whole travel package as JSON).
     */
    private const LONG_ANSWER_TOKENS = 8000;

    /**
     * Process a message through the configured AI provider.
     */
    public function processMessage(string $messageContent, Lead $lead, bool $recordUserMessage = true): string
    {
        try {
            if ($recordUserMessage) {
                $lead->messages()->create([
                    'role' => 'user',
                    'content' => $messageContent,
                ]);
            }

            $this->updateLeadTemperature($messageContent, $lead);

            $settings = get_agency_settings();
            $provider = $settings?->ai_provider ?? AiProvider::None;

            if ($provider === AiProvider::None) {
                return $this->fallbackBotLogic($messageContent, $lead);
            }

            $systemPrompt = $this->buildSystemPrompt($messageContent, $lead);

            $responseText = $this->callAiWithRetry("{$systemPrompt}\nAsistente:");

            $lead->messages()->create([
                'role' => 'assistant',
                'content' => $responseText,
            ]);

            return $responseText;
        } catch (Throwable $e) {
            Log::error('AiConciergeService Error: '.$e->getMessage());

            if (in_array($e->getMessage(), ['AI_API_KEY_MISSING', 'GEMINI_API_KEY_MISSING'])) {
                return $this->fallbackBotLogic($messageContent, $lead);
            }

            return 'Disculpá, estoy teniendo un pequeño problema técnico. ¿Podés intentar de nuevo en unos segundos? 🙏';
        }
    }

    /**
     * Process a message through the configured AI with streaming response.
     */
    public function processMessageStream(string $messageContent, Lead $lead): \Generator
    {
        try {
            $lead->messages()->create([
                'role' => 'user',
                'content' => $messageContent,
            ]);

            $this->updateLeadTemperature($messageContent, $lead);

            $systemPrompt = $this->buildSystemPrompt($messageContent, $lead);

            $settings = get_agency_settings();
            $provider = $settings?->ai_provider ?? AiProvider::None;

            if ($provider === AiProvider::None) {
                yield $this->fallbackBotLogic($messageContent, $lead);

                return;
            }

            $apiKey = $this->resolveApiKey($settings);

            if (empty($apiKey)) {
                throw new \Exception('AI_API_KEY_MISSING');
            }

            $fullResponse = '';

            if ($provider === AiProvider::Gemini) {
                config(['gemini.api_key' => $apiKey]);
                $stream = Gemini::generativeModel('models/'.$this->resolveModel($settings, $provider))
                    ->generateContentStream("{$systemPrompt}\nAsistente:");

                foreach ($stream as $response) {
                    $chunk = $response->text();
                    $fullResponse .= $chunk;
                    yield $chunk;
                }
            } else {
                // OpenAI-compatible providers do not stream through simple HTTP — yield full response
                $fullResponse = $this->callOpenAiCompatible($provider, "{$systemPrompt}\nAsistente:", $apiKey, $this->resolveModel($settings, $provider));
                yield $fullResponse;
            }

            $lead->messages()->create([
                'role' => 'assistant',
                'content' => $fullResponse,
            ]);

        } catch (Throwable $e) {
            Log::error('AiConciergeService Stream Error: '.$e->getMessage());

            if (in_array($e->getMessage(), ['AI_API_KEY_MISSING', 'GEMINI_API_KEY_MISSING'])) {
                yield $this->fallbackBotLogic($messageContent, $lead);
            } else {
                yield 'Disculpá, estoy teniendo un pequeño problema técnico. ¿Podés intentar de nuevo? 🙏';
            }
        }
    }

    /**
     * Extract structured data from a message using AI.
     */
    public function extractLeadData(string $message): array
    {
        try {
            $prompt = "Analiza el historial y extrae JSON estricto:
            - 'destino': Lugar de destino mencionado.
            - 'presupuesto': Monto o rango presupuestario mencionado (ej: 'USD 2000', 'ARS 500000').
            - 'pasajeros': Cantidad exacta como texto descriptivo (Ej: '4 adultos, 3 niños'. IMPORTANTE: NO juntes ni sumes los números, dejalo como string descriptivo).
            - 'fecha': Fecha o período del viaje mencionado (ej: 'primera quincena de agosto', 'septiembre 2026', '15/09/2026').
            - 'noches': Número de noches mencionado como entero. null si no se mencionó.
            - 'ciudad_salida': Ciudad desde donde salen los pasajeros. null si no se mencionó.
            - 'nombre': Nombre del usuario si se presentó (ej: 'Hola soy Juan' → 'Juan').
            - 'resumen': Resumen corto INCLUYENDO: destino, fechas/quincena, noches, ciudad de salida, pasajeros y presupuesto si están.
            - 'requiere_atencion': true si pide humano o parece molesto.

            Texto: \"{$message}\"";

            $text = $this->callAiWithRetry($prompt);
            $text = str_replace(['```json', '```'], '', $text);

            return json_decode($text, true) ?? [];
        } catch (Throwable $e) {
            Log::error('AiExtraction Error: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Resolve the effective API key: prefer new ai_api_key, fall back to legacy gemini_api_key.
     */
    private function resolveApiKey(mixed $settings): ?string
    {
        if (! empty($settings?->ai_api_key)) {
            return $settings->ai_api_key;
        }

        // Backward compatibility: use old gemini_api_key if no new key is set
        return $settings?->gemini_api_key ?? null;
    }

    /**
     * The model the agency chose, or the provider's default when none was set.
     */
    private function resolveModel(mixed $settings, AiProvider $provider): string
    {
        return filled($settings?->ai_model) ? trim($settings->ai_model) : $provider->getDefaultModel();
    }

    /**
     * Check that the configured provider, key and model answer. Returns the error text, or null when it works.
     */
    public function testConnection(): ?string
    {
        try {
            $this->callAiWithRetry('Respondé solo con la palabra OK.', retries: 0);

            return null;
        } catch (Throwable $e) {
            return $e->getMessage() === 'AI_DISABLED'
                ? 'La IA está desactivada: elegí un proveedor.'
                : (in_array($e->getMessage(), ['AI_API_KEY_MISSING', 'GEMINI_API_KEY_MISSING']) ? 'Falta cargar la API key.' : $e->getMessage());
        }
    }

    /**
     * Call the configured AI provider with retry logic.
     */
    private function callAiWithRetry(string $prompt, ?string $pdfPath = null, int $retries = self::MAX_RETRIES, bool $json = false): string
    {
        $settings = get_agency_settings();
        $provider = $settings?->ai_provider ?? AiProvider::None;

        if ($provider === AiProvider::None) {
            throw new \Exception('AI_DISABLED');
        }

        $apiKey = $this->resolveApiKey($settings);

        if (empty($apiKey)) {
            throw new \Exception('AI_API_KEY_MISSING');
        }

        if ($pdfPath !== null && ! $provider->supportsPdf()) {
            throw new \Exception("{$provider->getLabel()} no puede leer PDFs. Usá Gemini u OpenAI, o importá desde un enlace.");
        }

        $model = $this->resolveModel($settings, $provider);
        $lastException = null;

        for ($attempt = 0; $attempt <= $retries; $attempt++) {
            try {
                if ($attempt > 0) {
                    usleep($attempt * 500_000);
                }

                if ($pdfPath !== null) {
                    return match ($provider) {
                        AiProvider::Gemini => $this->callGeminiWithPdf($prompt, $pdfPath, $apiKey, $model, $json),
                        default => $this->callOpenAiWithPdf($prompt, $pdfPath, $apiKey, $model, $json),
                    };
                }

                return match ($provider) {
                    AiProvider::Gemini => $this->callGemini($prompt, $apiKey, $model, $json),
                    default => $this->callOpenAiCompatible($provider, $prompt, $apiKey, $model, $json),
                };
            } catch (Throwable $e) {
                $lastException = $e;
                Log::warning("AI API attempt {$attempt} failed: ".$e->getMessage());
            }
        }

        throw $lastException;
    }

    /**
     * Run a one-off prompt with the agency's configured provider and key, optionally reading a PDF.
     * With $json the provider is asked for a JSON object and may answer at length (trip imports).
     *
     * @throws Throwable When the AI is disabled, has no key, or every retry failed.
     */
    public function generate(string $prompt, ?string $pdfPath = null, bool $json = false): string
    {
        return $this->callAiWithRetry($prompt, $pdfPath, json: $json);
    }

    /**
     * Gemini reading a PDF directly (works for scanned PDFs too).
     */
    private function callGeminiWithPdf(string $prompt, string $pdfPath, string $apiKey, string $model, bool $json): string
    {
        $response = Http::timeout(90)
            ->withHeaders(['x-goog-api-key' => $apiKey])
            ->post('https://generativelanguage.googleapis.com/v1beta/models/'.$model.':generateContent', [
                'contents' => [[
                    'parts' => [
                        ['text' => $prompt],
                        ['inline_data' => ['mime_type' => 'application/pdf', 'data' => base64_encode((string) file_get_contents($pdfPath))]],
                    ],
                ]],
                'generationConfig' => $json
                    ? ['responseMimeType' => 'application/json', 'maxOutputTokens' => self::LONG_ANSWER_TOKENS]
                    : ['maxOutputTokens' => self::LONG_ANSWER_TOKENS],
            ]);

        if ($response->failed()) {
            throw new \Exception('Gemini API error: '.$response->body());
        }

        return (string) $response->json('candidates.0.content.parts.0.text');
    }

    /**
     * OpenAI reading a PDF directly.
     */
    private function callOpenAiWithPdf(string $prompt, string $pdfPath, string $apiKey, string $model, bool $json): string
    {
        $response = Http::withToken($apiKey)
            ->timeout(90)
            ->post(AiProvider::OpenAI->getChatCompletionsUrl(), [
                'model' => $model,
                'messages' => [[
                    'role' => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => $prompt],
                        ['type' => 'file', 'file' => [
                            'filename' => basename($pdfPath),
                            'file_data' => 'data:application/pdf;base64,'.base64_encode((string) file_get_contents($pdfPath)),
                        ]],
                    ],
                ]],
                'max_tokens' => self::LONG_ANSWER_TOKENS,
                ...($json ? ['response_format' => ['type' => 'json_object']] : []),
            ]);

        if ($response->failed()) {
            throw new \Exception('OpenAI API error: '.$response->body());
        }

        return (string) $response->json('choices.0.message.content');
    }

    /**
     * Call Gemini API.
     */
    private function callGemini(string $prompt, string $apiKey, string $model, bool $json = false): string
    {
        config(['gemini.api_key' => $apiKey]);
        $generator = Gemini::generativeModel('models/'.$model);

        if ($json) {
            $generator = $generator->withGenerationConfig(new GenerationConfig(
                maxOutputTokens: self::LONG_ANSWER_TOKENS,
                responseMimeType: ResponseMimeType::APPLICATION_JSON,
            ));
        }

        $result = $generator->generateContent($prompt);

        return $result->text();
    }

    /**
     * Call a Chat Completions API (OpenAI, or any provider that speaks the same protocol, like Groq).
     */
    private function callOpenAiCompatible(AiProvider $provider, string $prompt, string $apiKey, string $model, bool $json = false): string
    {
        $response = Http::withToken($apiKey)
            ->timeout($json ? 120 : 30)
            ->post($provider->getChatCompletionsUrl(), [
                'model' => $model,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => $json ? self::LONG_ANSWER_TOKENS : 2000,
                'temperature' => $json ? 0.2 : 0.7,
                ...($json ? ['response_format' => ['type' => 'json_object']] : []),
            ]);

        if ($response->failed()) {
            throw new \Exception("{$provider->getLabel()} API error: ".$response->body());
        }

        return $response->json('choices.0.message.content') ?? '';
    }

    /**
     * Build the system prompt with lead context and history.
     */
    private function buildSystemPrompt(string $messageContent, Lead $lead): string
    {
        $history = $lead->messages()
            ->orderBy('created_at', 'desc')
            ->take(self::CONTEXT_MESSAGE_COUNT)
            ->get()
            ->reverse();

        $context = '';
        foreach ($history as $msg) {
            $role = $msg->role === 'user' ? 'Usuario' : 'Asistente';
            $context .= "{$role}: {$msg->content}\n";
        }

        $leadContext = '';
        $aiData = $lead->ai_data ?? [];
        if (! empty($aiData)) {
            $leadContext = "\n\nDATOS DEL LEAD:";
            if (! empty($aiData['destino'])) {
                $leadContext .= "\n- Destino: {$aiData['destino']}";
            }
            if (! empty($aiData['presupuesto'])) {
                $leadContext .= "\n- Presupuesto: {$aiData['presupuesto']}";
            }
            if (! empty($aiData['pasajeros'])) {
                $leadContext .= "\n- Pasajeros: {$aiData['pasajeros']}";
            }
        }
        if ($lead->customer_name && $lead->customer_name !== 'Web Guest') {
            $leadContext .= "\n- Nombre: {$lead->customer_name}";
        }

        $settings = get_agency_settings();
        $whatsappLinks = collect($settings?->social_links ?? [])
            ->filter(fn ($link) => str_contains(strtolower($link['platform'] ?? ''), 'whatsapp') ||
                str_contains(strtolower($link['icon'] ?? ''), 'whatsapp')
            );

        $names = $whatsappLinks->map(function ($link) {
            $displayName = str_ireplace('WhatsApp', '', $link['platform'] ?? '');

            return trim($displayName) ?: 'nuestros agentes';
        })->filter()->unique();

        $namesString = $names->isNotEmpty() ? $names->join(', ', ' o ') : 'nuestros asesores';
        $assistantName = $settings?->ai_assistant_name ?: 'Asistente';
        $companyName = $settings?->company_name ?? config('app.name', 'nuestra agencia de viajes');

        $systemPrompt = "Eres '{$assistantName}', la asistente virtual de {$companyName}.
            TU OBJETIVO: Recabar información CLAVE de forma RÁPIDA y ESCUETA. No des consejos ni sugieras vuelos/hoteles. Solo pregunta.
            
            DATOS A CONSEGUIR (Uno por uno, en orden, no abrumes):
            1. Destino y Pasajeros (si no los mencionaron aún).
            2. Fecha aprox del viaje: ¿Primera o segunda quincena? ¿Tiene flexibilidad?
            3. Ciudad de salida (desde dónde quieren salir, no ofrezcas transporte).
            4. Presupuesto estimado (en USD o ARS). SIEMPRE preguntalo — es imprescindible para armar una propuesta real.
            5. Cantidad de noches si no quedó claro de la fecha.
            
            CIERRE:
            Una vez que tengas estos datos, CIERRA LA CALIFICACIÓN con este mensaje exacto:
            '¡Perfecto! Ya tengo todo lo que necesito. {$namesString} se van a comunicar con vos a la brevedad para armarte la propuesta. 😉'
            
            REGLAS:
            - Sé MUY BREVE. Máximo 1 oración por respuesta.
            - Si preguntan precios o vuelos exactos, di que eso lo arman {$namesString}.
            - Si el presupuesto ya está en DATOS DEL LEAD, no lo vuelvas a preguntar.
            - No inventes nada.{$leadContext}";

        if (! empty($context)) {
            $systemPrompt .= "\n\nHISTORIAL:\n{$context}";
        }

        return $systemPrompt;
    }

    /**
     * Update lead temperature based on message keywords.
     */
    private function updateLeadTemperature(string $messageContent, Lead $lead): void
    {
        $lowerMsg = strtolower($messageContent);

        if (str_contains($lowerMsg, 'humano') || str_contains($lowerMsg, 'agente') || str_contains($lowerMsg, 'asesor')) {
            $lead->update([
                'temperature' => LeadTemperature::Hot,
                'needs_human_attention' => true,
            ]);
        } elseif ($lead->temperature === LeadTemperature::Cool && (str_contains($lowerMsg, 'fecha') || str_contains($lowerMsg, 'presupuesto') || str_contains($lowerMsg, 'reserva'))) {
            $lead->update(['temperature' => LeadTemperature::Warm]);
        }
    }

    /**
     * Fallback logic when AI is not configured.
     */
    private function fallbackBotLogic(string $userMsg, Lead $lead): string
    {
        $aiData = is_array($lead->ai_data) ? $lead->ai_data : [];

        $lastAssistantMsg = $lead->messages()->where('role', 'assistant')->latest()->first();
        $lastText = $lastAssistantMsg ? strtolower($lastAssistantMsg->content) : '';

        if (str_contains($lastText, 'destino en mente')) {
            $aiData['destino'] = $userMsg;
            $reply = '¿Tenés alguna fecha o mes en mente para el viaje?';
        } elseif (str_contains($lastText, 'fecha')) {
            $aiData['fecha'] = $userMsg;
            $reply = '¿Cuántos pasajeros viajan en total? (Si hay niños, por favor indicame las edades)';
        } elseif (str_contains($lastText, 'pasajeros')) {
            $aiData['pasajeros'] = $userMsg;
            $reply = '¿Desde qué ciudad les gustaría salir?';
        } elseif (str_contains($lastText, 'ciudad')) {
            $aiData['origen'] = $userMsg;
            $reply = '¿Tenés un presupuesto estimado en mente? (en USD o ARS — es para orientar la propuesta, no te preocupes si no lo tenés exacto)';
        } elseif (str_contains($lastText, 'presupuesto estimado')) {
            $aiData['presupuesto'] = $userMsg;
            $settings = get_agency_settings();
            $whatsappLinks = collect($settings?->social_links ?? [])
                ->filter(fn ($link) => str_contains(strtolower($link['platform'] ?? ''), 'whatsapp') ||
                    str_contains(strtolower($link['icon'] ?? ''), 'whatsapp')
                );
            $names = $whatsappLinks->map(function ($link) {
                return trim(str_ireplace('WhatsApp', '', $link['platform'] ?? '')) ?: 'nuestros agentes';
            })->filter()->unique();
            $namesString = $names->isNotEmpty() ? $names->join(', ', ' o ') : 'nuestros agentes';

            $reply = "¡Perfecto! Ya tengo todo lo que necesito. {$namesString} se van a comunicar con vos a la brevedad para armarte la propuesta. 😉";
        } else {
            $reply = 'Gracias por la información. En breve nos comunicaremos con vos.';
        }

        $lead->update(['ai_data' => $aiData]);

        $lead->messages()->create([
            'role' => 'assistant',
            'content' => $reply,
        ]);

        return $reply;
    }

    /**
     * Generate an itinerary based on a user prompt using AI.
     */
    public function generateItinerary(string $prompt): array
    {
        $systemPrompt = "Eres un agente de viajes experto. Genera un itinerario detallado en formato JSON estricto basándote en la siguiente solicitud: '{$prompt}'.
El formato JSON debe ser un array de objetos, donde cada objeto represente un día y tenga exactamente las siguientes claves:
- 'day': número de día (entero o string, ej. '1' o 1)
- 'title': título breve del día (ej. 'Llegada y City Tour')
- 'description': descripción de las actividades del día

Responde ÚNICAMENTE con el array JSON crudo, sin bloques de código markdown, sin prefijos y sin explicaciones adicionales.";

        try {
            $text = $this->callAiWithRetry($systemPrompt);

            // Clean markdown blocks if the model still adds them
            $text = preg_replace('/```json\s*/i', '', $text);
            $text = preg_replace('/```\s*/', '', $text);

            $decoded = json_decode(trim($text), true);

            return is_array($decoded) ? $decoded : [];
        } catch (Throwable $e) {
            Log::error('Error generating itinerary with AI: '.$e->getMessage());

            return [];
        }
    }
}

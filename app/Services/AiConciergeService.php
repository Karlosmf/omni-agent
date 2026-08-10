<?php

namespace App\Services;

use App\Enums\AiProvider;
use App\Enums\LeadTemperature;
use App\Models\Lead;
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
     * Process a message through the configured AI provider.
     */
    public function processMessage(string $messageContent, Lead $lead): string
    {
        try {
            $lead->messages()->create([
                'role' => 'user',
                'content' => $messageContent,
            ]);

            $this->updateLeadTemperature($messageContent, $lead);

            $settings = get_agency_settings();
            $provider = $settings?->ai_provider ?? AiProvider::None;

            if ($provider === AiProvider::None) {
                return $this->fallbackBotLogic($messageContent, $lead);
            }

            $systemPrompt = $this->buildSystemPrompt($messageContent, $lead);

            $responseText = $this->callAiWithRetry("{$systemPrompt}\nUsuario: {$messageContent}");

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
                $stream = Gemini::generativeModel('models/gemini-2.0-flash')
                    ->generateContentStream("{$systemPrompt}\nUsuario: {$messageContent}");

                foreach ($stream as $response) {
                    $chunk = $response->text();
                    $fullResponse .= $chunk;
                    yield $chunk;
                }
            } else {
                // OpenAI does not support true streaming through simple HTTP — yield full response
                $fullResponse = $this->callOpenAi("{$systemPrompt}\nUsuario: {$messageContent}", $apiKey);
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
     * Call the configured AI provider with retry logic.
     */
    private function callAiWithRetry(string $prompt): string
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

        $lastException = null;

        for ($attempt = 0; $attempt <= self::MAX_RETRIES; $attempt++) {
            try {
                if ($attempt > 0) {
                    usleep($attempt * 500_000);
                }

                return match ($provider) {
                    AiProvider::Gemini => $this->callGemini($prompt, $apiKey),
                    AiProvider::OpenAI => $this->callOpenAi($prompt, $apiKey),
                };
            } catch (Throwable $e) {
                $lastException = $e;
                Log::warning("AI API attempt {$attempt} failed: ".$e->getMessage());
            }
        }

        throw $lastException;
    }

    /**
     * Call Gemini API.
     */
    private function callGemini(string $prompt, string $apiKey): string
    {
        config(['gemini.api_key' => $apiKey]);
        $result = Gemini::generativeModel('models/gemini-2.0-flash')->generateContent($prompt);

        return $result->text();
    }

    /**
     * Call OpenAI Chat Completions API.
     */
    private function callOpenAi(string $prompt, string $apiKey): string
    {
        $response = Http::withToken($apiKey)
            ->timeout(30)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 500,
                'temperature' => 0.7,
            ]);

        if ($response->failed()) {
            throw new \Exception('OpenAI API error: '.$response->body());
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

        $namesString = $names->isNotEmpty() ? $names->join(', ', ' o ') : 'Nela o Belén';
        $assistantName = $settings?->ai_assistant_name ?? 'Brisa';
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
            $response = Gemini::geminiPro()->generateContent($systemPrompt);
            $text = $response->text();

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
